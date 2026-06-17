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
$dpLocked = !empty($data['purchase_locked']);
$dpPurchaseId = (int) ($pData['id'] ?? 0);
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
                            <?php
                            $exoticVendorId = trim((string) ($v['vendor_id'] ?? ''));
                            $vendorLabel = trim((string) ($v['vendor_name'] ?? ''));
                            if ($exoticVendorId !== '' && $vendorLabel !== '') {
                                $vendorLabel = $exoticVendorId . '-' . $vendorLabel;
                            } elseif ($exoticVendorId !== '') {
                                $vendorLabel = $exoticVendorId;
                            }
                            ?>
                            <option value="<?= (int) $v['id'] ?>"
                                <?= ($purchase && (int) ($purchase['vendor_id'] ?? 0) === (int) $v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vendorLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Warehouse (stock) <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="<?= $inp ?> bg-white" <?= $dpLocked ? 'disabled' : 'required' ?>>
                        <option value="">Select warehouse</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <?php
                            $whId = (int) ($wh['id'] ?? 0);
                            $isWhSelected = $isEdit
                                ? ((int) ($pData['warehouse_id'] ?? 0) === $whId)
                                : !empty($wh['is_default']);
                            ?>
                            <option value="<?= $whId ?>"
                                <?= $isWhSelected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wh['address_title'] ?? ('#' . (int) ($wh['id'] ?? 0))) ?>
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
                            <th class="px-2 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 dp-col-cost">Cost / item</th>
                            <th class="px-2 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 dp-col-qty">Qty</th>
                            <th class="px-2 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 dp-col-hsn">HSN</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[6rem]">GST %</th>
                            <th class="px-2 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 dp-col-unit">Unit</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[8rem]">Line total</th>
                            <th class="px-3 py-3 text-center font-semibold text-gray-700 border-b border-gray-200 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="line-items-body" class="divide-y divide-gray-100">
                        <?php foreach ($items as $idx => $it): ?>
                            <?php
                            $lineImg = trim((string) ($it['product_image'] ?? ''));
                            $thumbShow = $lineImg !== '' ? $lineImg : $dpThumbPlaceholder;
                            $dpLineItemId = (int) ($it['id'] ?? 0);
                            $dpVendorQtySynced = (int) ($it['vendor_qty_synced'] ?? 0) === 1;
                            $dpVendorQtySyncedQty = isset($it['vendor_qty_synced_qty']) && $it['vendor_qty_synced_qty'] !== null
                                ? (string) $it['vendor_qty_synced_qty']
                                : '';
                            ?>
                            <tr class="dp-line hover:bg-amber-50/30 transition-colors"
                                data-dp-item-id="<?= $dpLineItemId ?>"
                                data-vendor-qty-synced="<?= $dpVendorQtySynced ? '1' : '0' ?>"
                                data-vendor-qty-synced-qty="<?= htmlspecialchars($dpVendorQtySyncedQty, ENT_QUOTES, 'UTF-8') ?>">
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
                                    <div class="flex items-start gap-1">
                                        <div class="relative dp-sku-cell flex-1 min-w-0">
                                            <input type="hidden" name="item_code[]" class="dp-h-item-code" value="<?= htmlspecialchars($it['item_code'] ?? '') ?>">
                                            <input type="hidden" name="color[]" class="dp-h-color" value="<?= htmlspecialchars($it['color'] ?? '') ?>">
                                            <input type="hidden" name="size[]" class="dp-h-size" value="<?= htmlspecialchars($it['size'] ?? '') ?>">
                                            <input type="hidden" name="gst_amount[]" class="dp-gst" value="<?= htmlspecialchars((string) ($it['gst_amount'] ?? '')) ?>">
                                            <input type="text" name="sku[]" autocomplete="off" placeholder="Search by SKU…"
                                                class="dp-sku w-full min-w-[10rem] <?= $inpSm ?>"
                                                value="<?= htmlspecialchars($it['sku'] ?? '') ?>">
                                            <div class="dp-sku-suggestions max-h-52 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg hidden text-left"></div>
                                        </div>
                                        <button type="button" class="dp-fetch-pending-orders shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Fetch pending orders for this SKU" aria-label="Fetch pending orders for this SKU" <?= $dpLocked ? 'disabled' : '' ?>>
                                            <i class="fas fa-clipboard-list text-xs" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-1 py-2 align-top dp-col-cost">
                                    <div class="flex items-center gap-0.5">
                                        <input type="number" step="0.0001" name="cost_per_item[]" class="dp-cost dp-inp-compact <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['cost_per_item'] ?? '')) ?>">
                                        <div class="dp-cost-actions shrink-0">
                                            <button type="button" class="dp-fetch-price dp-line-mini-btn inline-flex items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                title="Fetch latest cost from product API" aria-label="Fetch latest cost from product API" <?= $dpLocked ? 'disabled' : '' ?>>
                                                <i class="fas fa-arrow-down" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="dp-verify-vendor dp-line-mini-btn inline-flex items-center justify-center rounded-md border border-teal-200 bg-teal-50 text-teal-800 hover:bg-teal-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                title="Verify CP and stock on exoticindia.com" aria-label="Verify CP and stock on exoticindia.com" <?= $dpLocked ? 'disabled' : '' ?>>
                                                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-1 py-2 align-top dp-col-qty">
                                    <div class="flex items-center gap-0.5">
                                        <input type="number" step="0.001" name="qty[]" class="dp-qty dp-inp-compact <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['qty'] ?? '')) ?>">
                                        <button type="button" class="dp-sync-vendor-qty dp-line-mini-btn inline-flex items-center justify-center rounded-md border disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 <?= $dpVendorQtySynced ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-violet-200 bg-violet-50 text-violet-800 hover:bg-violet-100' ?>"
                                            title="<?= $dpVendorQtySynced ? 'Qty synced to vendor API' : ($dpLineItemId > 0 ? 'Push qty to vendor API' : 'Save purchase first to sync qty') ?>"
                                            aria-label="<?= $dpVendorQtySynced ? 'Qty synced to vendor API' : 'Push qty to vendor API' ?>"
                                            <?= ($dpLocked || ($dpLineItemId <= 0 && !$dpVendorQtySynced)) ? 'disabled' : '' ?>>
                                            <i class="fas <?= $dpVendorQtySynced ? 'fa-check' : 'fa-cloud-upload-alt' ?>" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-1 py-2 align-top dp-col-hsn"><input name="hsn[]" class="dp-inp-cell w-full <?= $inpSm ?>" value="<?= htmlspecialchars($it['hsn'] ?? '') ?>"></td>
                                <td class="px-3 py-2 align-top min-w-[6rem]"><input type="number" step="0.01" name="gst_rate[]" class="dp-rate w-full min-w-[5rem] <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['gst_rate'] ?? '')) ?>"></td>
                                <td class="px-1 py-2 align-top dp-col-unit"><input name="unit[]" class="dp-inp-cell w-full <?= $inpSm ?>" value="<?= htmlspecialchars($it['unit'] ?? '') ?>"></td>
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
                Type at least 2 characters to search products by SKU only. Use the <i class="fas fa-arrow-down text-[10px]" aria-hidden="true"></i> button beside cost to pull the latest cost from the product API (updates <code class="text-[11px] bg-gray-100 px-1 rounded">vp_products</code>). Use <i class="fas fa-clipboard-check text-[10px]" aria-hidden="true"></i> to verify CP and local stock on exoticindia.com against this line and <code class="text-[11px] bg-gray-100 px-1 rounded">vp_products</code>. Line amounts recalc from cost, qty, and GST %.
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

<div id="dp-status-modal" class="fixed inset-0 z-[210] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="dp-status-modal-title" aria-describedby="dp-status-modal-message">
    <button type="button" id="dp-status-modal-backdrop" class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]" aria-label="Close dialog"></button>
    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-amber-200/40 bg-white shadow-2xl shadow-amber-900/10 ring-1 ring-black/5 animate-[dpModalIn_0.22s_ease-out]">
        <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-amber-200/30 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-6 pt-7 pb-5 text-center">
            <div id="dp-status-modal-icon-wrap" class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border shadow-sm">
                <i id="dp-status-modal-icon" class="text-2xl" aria-hidden="true"></i>
            </div>
            <h3 id="dp-status-modal-title" class="text-lg font-bold tracking-tight text-gray-900 mb-2"></h3>
            <p id="dp-status-modal-message" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"></p>
        </div>
        <div class="relative border-t border-gray-100 bg-gradient-to-b from-gray-50/90 to-white px-6 py-4 flex justify-center">
            <button type="button" id="dp-status-modal-ok"
                class="inline-flex min-w-[7rem] items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-amber-900/15 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
                OK
            </button>
        </div>
    </div>
</div>

<div id="dp-verify-vendor-modal" class="fixed inset-0 z-[220] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="dp-verify-vendor-title">
    <button type="button" id="dp-verify-vendor-backdrop" class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]" aria-label="Close dialog"></button>
    <div class="relative flex w-full max-w-lg max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-teal-200/40 bg-white shadow-2xl shadow-teal-900/10 ring-1 ring-black/5 animate-[dpModalIn_0.22s_ease-out]">
        <div class="border-b border-gray-100 bg-gradient-to-r from-teal-50/80 to-white px-6 py-4">
            <div class="flex items-start gap-3">
                <div id="dp-verify-vendor-icon-wrap" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-teal-200 bg-teal-50 text-teal-700">
                    <i id="dp-verify-vendor-icon" class="fas fa-clipboard-check" aria-hidden="true"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="dp-verify-vendor-title" class="text-lg font-bold tracking-tight text-gray-900">Vendor verification</h3>
                    <p id="dp-verify-vendor-subtitle" class="mt-1 text-sm text-gray-600"></p>
                    <p id="dp-verify-vendor-message" class="mt-2 text-sm text-gray-700"></p>
                </div>
            </div>
        </div>
        <div class="flex-1 overflow-auto px-6 py-4">
            <div id="dp-verify-vendor-loading" class="hidden py-10 text-center text-sm text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i>Checking exoticindia.com…
            </div>
            <table id="dp-verify-vendor-table" class="hidden w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="pb-2 pr-3">Field</th>
                        <th class="pb-2 pr-3 text-right">Expected</th>
                        <th class="pb-2 pr-3 text-right">Vendor</th>
                        <th class="pb-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="dp-verify-vendor-tbody" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 bg-gray-50/80 px-6 py-4 flex justify-end gap-2">
            <button type="button" id="dp-verify-vendor-close"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 transition">
                Close
            </button>
        </div>
    </div>
</div>

<div id="dp-pending-orders-modal" class="fixed inset-0 z-[220] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="dp-pending-orders-title">
    <button type="button" id="dp-pending-orders-backdrop" class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]" aria-label="Close dialog"></button>
    <div class="relative flex w-full max-w-lg max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-sky-200/40 bg-white shadow-2xl shadow-sky-900/10 ring-1 ring-black/5 animate-[dpModalIn_0.22s_ease-out]">
        <div class="border-b border-gray-100 bg-gradient-to-r from-sky-50/80 to-white px-6 py-4">
            <h3 id="dp-pending-orders-title" class="text-lg font-bold tracking-tight text-gray-900">Pending orders for SKU</h3>
            <p id="dp-pending-orders-subtitle" class="mt-1 text-sm text-gray-600"></p>
            <p id="dp-pending-orders-import-status" class="mt-2 text-xs font-medium text-sky-700 hidden"></p>
        </div>
        <div class="flex-1 overflow-auto px-6 py-4">
            <div id="dp-pending-orders-loading" class="hidden py-10 text-center text-sm text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i>Fetching orders…
            </div>
            <div id="dp-pending-orders-empty" class="hidden py-10 text-center text-sm text-gray-500">No pending orders found for this item in the selected date range.</div>
            <table id="dp-pending-orders-table" class="hidden w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="pb-2 pr-3">Order #</th>
                        <th class="pb-2 pr-3">SKU</th>
                        <th class="pb-2 text-right">Qty</th>
                    </tr>
                </thead>
                <tbody id="dp-pending-orders-tbody" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 bg-gray-50/80 px-6 py-4 flex justify-end gap-2">
            <button type="button" id="dp-pending-orders-close"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 transition">
                Close
            </button>
        </div>
    </div>
</div>

<style>
@keyframes dpModalIn {
    from { opacity: 0; transform: translateY(8px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
#line-items-table .dp-col-cost {
    width: 8rem;
    max-width: 8rem;
}
#line-items-table .dp-col-qty {
    width: 5.75rem;
    max-width: 5.75rem;
}
#line-items-table .dp-col-hsn {
    width: 5rem;
    max-width: 5rem;
}
#line-items-table .dp-col-unit {
    width: 3.75rem;
    max-width: 3.75rem;
}
#line-items-table .dp-inp-compact {
    min-width: 0;
    padding-left: 0.4rem;
    padding-right: 0.4rem;
    font-size: 0.8125rem;
}
#line-items-table .dp-col-cost .dp-inp-compact {
    width: 4.25rem;
    max-width: 4.25rem;
    flex: 0 0 4.25rem;
}
#line-items-table .dp-col-qty .dp-inp-compact {
    width: 3.5rem;
    max-width: 3.5rem;
    flex: 0 0 3.5rem;
}
#line-items-table .dp-inp-cell {
    min-width: 0;
    padding-left: 0.4rem;
    padding-right: 0.4rem;
    font-size: 0.8125rem;
}
#line-items-table .dp-line-mini-btn {
    width: 1.65rem;
    height: 1.65rem;
    min-width: 1.65rem;
    padding: 0;
    line-height: 1;
}
#line-items-table .dp-line-mini-btn i {
    font-size: 0.625rem;
}
#line-items-table .dp-cost-actions {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}
</style>

<table class="hidden">
    <tbody id="line-item-template">
        <tr class="dp-line hover:bg-amber-50/30 transition-colors" data-dp-item-id="0" data-vendor-qty-synced="0" data-vendor-qty-synced-qty="">
            <td class="px-2 py-2 align-top text-center w-16">
                <button type="button" class="dp-thumb-trigger mx-auto flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-0 opacity-60 cursor-not-allowed shadow-sm"
                    data-full-src="" title="" aria-label="No product image">
                    <img src="<?= htmlspecialchars($dpThumbPlaceholder) ?>" alt="" class="dp-line-thumb-img h-full w-full object-cover" loading="lazy" data-placeholder="<?= htmlspecialchars($dpThumbPlaceholder) ?>"
                        onerror="this.onerror=null;this.src=this.getAttribute('data-placeholder')||'';">
                </button>
            </td>
            <td class="px-3 py-2 align-top min-w-[14rem]">
                <div class="flex items-start gap-1">
                    <div class="relative dp-sku-cell flex-1 min-w-0">
                        <input type="hidden" name="item_code[]" class="dp-h-item-code" value="">
                        <input type="hidden" name="color[]" class="dp-h-color" value="">
                        <input type="hidden" name="size[]" class="dp-h-size" value="">
                        <input type="hidden" name="gst_amount[]" class="dp-gst" value="">
                        <input type="text" name="sku[]" autocomplete="off" placeholder="Search by SKU…"
                            class="dp-sku w-full min-w-[10rem] <?= $inpSm ?>" value="">
                        <div class="dp-sku-suggestions max-h-52 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg hidden text-left"></div>
                    </div>
                    <button type="button" class="dp-fetch-pending-orders shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Fetch pending orders for this SKU" aria-label="Fetch pending orders for this SKU">
                        <i class="fas fa-clipboard-list text-xs" aria-hidden="true"></i>
                    </button>
                </div>
            </td>
            <td class="px-1 py-2 align-top dp-col-cost">
                <div class="flex items-center gap-0.5">
                    <input type="number" step="0.0001" name="cost_per_item[]" class="dp-cost dp-inp-compact <?= $inpSm ?>" value="">
                    <div class="dp-cost-actions shrink-0">
                        <button type="button" class="dp-fetch-price dp-line-mini-btn inline-flex items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Fetch latest cost from product API" aria-label="Fetch latest cost from product API">
                            <i class="fas fa-arrow-down" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="dp-verify-vendor dp-line-mini-btn inline-flex items-center justify-center rounded-md border border-teal-200 bg-teal-50 text-teal-800 hover:bg-teal-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Verify CP and stock on exoticindia.com" aria-label="Verify CP and stock on exoticindia.com">
                            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </td>
            <td class="px-1 py-2 align-top dp-col-qty">
                <div class="flex items-center gap-0.5">
                    <input type="number" step="0.001" name="qty[]" class="dp-qty dp-inp-compact <?= $inpSm ?>" value="1">
                    <button type="button" class="dp-sync-vendor-qty dp-line-mini-btn inline-flex items-center justify-center rounded-md border border-violet-200 bg-violet-50 text-violet-800 hover:bg-violet-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Save purchase first to sync qty" aria-label="Push qty to vendor API" disabled>
                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                    </button>
                </div>
            </td>
            <td class="px-1 py-2 align-top dp-col-hsn"><input name="hsn[]" class="dp-inp-cell w-full <?= $inpSm ?>" value=""></td>
            <td class="px-3 py-2 align-top min-w-[6rem]"><input type="number" step="0.01" name="gst_rate[]" class="dp-rate w-full min-w-[5rem] <?= $inpSm ?>" value=""></td>
            <td class="px-1 py-2 align-top dp-col-unit"><input name="unit[]" class="dp-inp-cell w-full <?= $inpSm ?>" value=""></td>
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
    var DP_PURCHASE_ID = <?= (int) $dpPurchaseId ?>;

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

    function fetchLinePriceUrl(itemCode, sku, color, size) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'fetch_line_price');
        u.searchParams.set('item_code', itemCode || '');
        u.searchParams.set('sku', sku || '');
        u.searchParams.set('color', color || '');
        u.searchParams.set('size', size || '');
        return u.toString();
    }

    var DP_STATUS_STYLES = {
        error: {
            title: 'Could not fetch price',
            iconWrap: 'bg-red-50 border-red-200 text-red-600',
            icon: 'fas fa-exclamation-circle'
        },
        warning: {
            title: 'Action needed',
            iconWrap: 'bg-amber-50 border-amber-200 text-amber-700',
            icon: 'fas fa-info-circle'
        },
        success: {
            title: 'Price updated',
            iconWrap: 'bg-emerald-50 border-emerald-200 text-emerald-600',
            icon: 'fas fa-check-circle'
        }
    };

    function dpCloseStatusModal() {
        var modal = document.getElementById('dp-status-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function dpShowStatusModal(message, type, titleOverride) {
        var modal = document.getElementById('dp-status-modal');
        var titleEl = document.getElementById('dp-status-modal-title');
        var msgEl = document.getElementById('dp-status-modal-message');
        var iconWrap = document.getElementById('dp-status-modal-icon-wrap');
        var iconEl = document.getElementById('dp-status-modal-icon');
        if (!modal || !titleEl || !msgEl || !iconWrap || !iconEl) {
            window.alert(message || 'Notice');
            return;
        }

        var style = DP_STATUS_STYLES[type] || DP_STATUS_STYLES.error;
        titleEl.textContent = titleOverride || style.title;
        msgEl.textContent = message || '';
        iconWrap.className = 'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border shadow-sm ' + style.iconWrap;
        iconEl.className = style.icon + ' text-2xl';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        var okBtn = document.getElementById('dp-status-modal-ok');
        if (okBtn) {
            okBtn.focus();
        }
    }

    function initDpStatusModal() {
        var modal = document.getElementById('dp-status-modal');
        if (!modal) return;
        var backdrop = document.getElementById('dp-status-modal-backdrop');
        var okBtn = document.getElementById('dp-status-modal-ok');
        if (backdrop) {
            backdrop.addEventListener('click', dpCloseStatusModal);
        }
        if (okBtn) {
            okBtn.addEventListener('click', dpCloseStatusModal);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                dpCloseStatusModal();
            }
        });
    }

    function dpFetchLatestPrice(tr, btn) {
        if (!tr || !btn || btn.disabled) {
            return;
        }
        var cell = tr.querySelector('.dp-sku-cell');
        var itemCode = cell && cell.querySelector('.dp-h-item-code') ? String(cell.querySelector('.dp-h-item-code').value || '').trim() : '';
        var sku = cell && cell.querySelector('.dp-sku') ? String(cell.querySelector('.dp-sku').value || '').trim() : '';
        var color = cell && cell.querySelector('.dp-h-color') ? String(cell.querySelector('.dp-h-color').value || '').trim() : '';
        var size = cell && cell.querySelector('.dp-h-size') ? String(cell.querySelector('.dp-h-size').value || '').trim() : '';
        if (!itemCode && !sku) {
            dpShowStatusModal('Select a product from SKU search or enter a SKU that is linked to an item code before fetching price.', 'warning');
            return;
        }

        var icon = btn.querySelector('i');
        var prevIconClass = icon ? icon.className : 'fas fa-arrow-down text-xs';
        btn.disabled = true;
        if (icon) {
            icon.className = 'fas fa-spinner fa-spin text-xs';
        }

        fetch(fetchLinePriceUrl(itemCode, sku, color, size), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    var errMsg = (data && data.message) ? data.message : 'Could not fetch latest price.';
                    var errTitle = (errMsg.indexOf('CP (cost price)') !== -1 || errMsg.indexOf('no cost price') !== -1 || errMsg.indexOf('(cp)') !== -1)
                        ? 'No CP from vendor API'
                        : 'Could not fetch price';
                    dpShowStatusModal(errMsg, 'error', errTitle);
                    return;
                }
                var cost = tr.querySelector('.dp-cost');
                if (cost && data.cost_price != null && data.cost_price !== '') {
                    cost.value = data.cost_price;
                }
                if (data.hsn) {
                    var hsn = tr.querySelector('input[name="hsn[]"]');
                    if (hsn && String(hsn.value || '').trim() === '') {
                        hsn.value = String(data.hsn);
                    }
                }
                if (data.gst != null && data.gst !== '') {
                    var rate = tr.querySelector('.dp-rate');
                    if (rate && String(rate.value || '').trim() === '') {
                        rate.value = data.gst;
                    }
                }
                recalcRow(tr);
            })
            .catch(function () {
                dpShowStatusModal('The price fetch request failed. Check your connection and try again.', 'error', 'Request failed');
            })
            .finally(function () {
                btn.disabled = false;
                if (icon) {
                    icon.className = prevIconClass;
                }
            });
    }

    function fetchPendingOrdersUrl(itemCode, sku, color, size) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'fetch_pending_orders');
        u.searchParams.set('item_code', itemCode || '');
        u.searchParams.set('sku', sku || '');
        u.searchParams.set('color', color || '');
        u.searchParams.set('size', size || '');
        return u.toString();
    }

    function importOrderUrl(orderNumber) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'import_order');
        u.searchParams.set('orderid', orderNumber || '');
        return u.toString();
    }

    function syncVendorQtyUrl(itemId, purchaseId) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'sync_vendor_qty');
        u.searchParams.set('item_id', String(itemId || ''));
        u.searchParams.set('purchase_id', String(purchaseId || ''));
        return u.toString();
    }

    function syncVendorQtyUrl(itemId, purchaseId) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'sync_vendor_qty');
        u.searchParams.set('item_id', String(itemId || ''));
        u.searchParams.set('purchase_id', String(purchaseId || ''));
        return u.toString();
    }

    function verifyVendorLineUrl(tr) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'verify_vendor_line');
        var itemId = parseInt(tr.getAttribute('data-dp-item-id') || '0', 10);
        if (itemId > 0 && DP_PURCHASE_ID > 0) {
            u.searchParams.set('item_id', String(itemId));
            u.searchParams.set('purchase_id', String(DP_PURCHASE_ID));
        } else {
            var itemCodeEl = tr.querySelector('.dp-h-item-code');
            var skuEl = tr.querySelector('.dp-sku');
            var colorEl = tr.querySelector('.dp-h-color');
            var sizeEl = tr.querySelector('.dp-h-size');
            u.searchParams.set('item_code', itemCodeEl ? String(itemCodeEl.value || '').trim() : '');
            u.searchParams.set('sku', skuEl ? String(skuEl.value || '').trim() : '');
            u.searchParams.set('color', colorEl ? String(colorEl.value || '').trim() : '');
            u.searchParams.set('size', sizeEl ? String(sizeEl.value || '').trim() : '');
        }
        var costEl = tr.querySelector('.dp-cost');
        var costVal = costEl ? String(costEl.value || '').trim() : '';
        if (costVal !== '') {
            u.searchParams.set('cost_per_item', costVal);
        }
        return u.toString();
    }

    function dpFormatVerifyNumber(val) {
        if (val === null || val === undefined || val === '') return '—';
        var n = parseFloat(val);
        if (isNaN(n)) return String(val);
        return Number.isInteger(n) ? String(n) : n.toFixed(4).replace(/\.?0+$/, '');
    }

    function dpCloseVerifyVendorModal() {
        var modal = document.getElementById('dp-verify-vendor-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function dpOpenVerifyVendorModalLoading(subtitle) {
        var modal = document.getElementById('dp-verify-vendor-modal');
        var loading = document.getElementById('dp-verify-vendor-loading');
        var table = document.getElementById('dp-verify-vendor-table');
        var subtitleEl = document.getElementById('dp-verify-vendor-subtitle');
        var messageEl = document.getElementById('dp-verify-vendor-message');
        var iconWrap = document.getElementById('dp-verify-vendor-icon-wrap');
        var icon = document.getElementById('dp-verify-vendor-icon');
        if (!modal) return;
        if (subtitleEl) subtitleEl.textContent = subtitle || '';
        if (messageEl) messageEl.textContent = '';
        if (loading) loading.classList.remove('hidden');
        if (table) table.classList.add('hidden');
        if (iconWrap) {
            iconWrap.className = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-teal-200 bg-teal-50 text-teal-700';
        }
        if (icon) icon.className = 'fas fa-clipboard-check';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function dpRenderVerifyVendorModal(data) {
        var loading = document.getElementById('dp-verify-vendor-loading');
        var table = document.getElementById('dp-verify-vendor-table');
        var tbody = document.getElementById('dp-verify-vendor-tbody');
        var subtitleEl = document.getElementById('dp-verify-vendor-subtitle');
        var messageEl = document.getElementById('dp-verify-vendor-message');
        var iconWrap = document.getElementById('dp-verify-vendor-icon-wrap');
        var icon = document.getElementById('dp-verify-vendor-icon');
        if (loading) loading.classList.add('hidden');

        var itemCode = (data && data.item_code) ? String(data.item_code) : '';
        var sku = (data && data.sku) ? String(data.sku) : '';
        var size = (data && data.size) ? String(data.size) : '';
        var color = (data && data.color) ? String(data.color) : '';
        var parts = [itemCode || sku];
        if (size) parts.push('size ' + size);
        if (color) parts.push(color);
        if (subtitleEl) subtitleEl.textContent = parts.filter(Boolean).join(' · ');

        var allMatch = !!(data && data.success);
        var partial = data && data.checks;
        if (messageEl) {
            messageEl.textContent = (data && data.message) ? data.message : (allMatch ? 'Vendor values match.' : 'Vendor verification failed.');
            messageEl.className = 'mt-2 text-sm ' + (allMatch ? 'text-emerald-700 font-medium' : 'text-amber-800 font-medium');
        }
        if (iconWrap && icon) {
            if (allMatch) {
                iconWrap.className = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700';
                icon.className = 'fas fa-check';
            } else if (partial) {
                iconWrap.className = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-700';
                icon.className = 'fas fa-exclamation-triangle';
            } else {
                iconWrap.className = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-700';
                icon.className = 'fas fa-times';
            }
        }

        if (!tbody || !partial) {
            if (table) table.classList.add('hidden');
            return;
        }

        var rows = [];
        ['cp', 'local_stock'].forEach(function (key) {
            var check = partial[key];
            if (!check) return;
            var statusHtml;
            if (!check.checked) {
                statusHtml = '<span class="text-xs text-gray-400">Skipped</span>';
            } else if (check.match) {
                statusHtml = '<span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700"><i class="fas fa-check" aria-hidden="true"></i>Match</span>';
            } else {
                statusHtml = '<span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700"><i class="fas fa-times" aria-hidden="true"></i>Mismatch</span>';
            }
            var note = check.note ? '<div class="text-xs text-gray-400 mt-0.5">' + dpEscHtml(check.note) + '</div>' : '';
            rows.push(
                '<tr>' +
                '<td class="py-2.5 pr-3 align-top font-medium text-gray-800">' + dpEscHtml(check.label || key) + note + '</td>' +
                '<td class="py-2.5 pr-3 align-top text-right tabular-nums text-gray-700">' + dpEscHtml(dpFormatVerifyNumber(check.expected)) + '</td>' +
                '<td class="py-2.5 pr-3 align-top text-right tabular-nums text-gray-900 font-medium">' + dpEscHtml(dpFormatVerifyNumber(check.vendor)) + '</td>' +
                '<td class="py-2.5 text-center align-top">' + statusHtml + '</td>' +
                '</tr>'
            );
        });

        tbody.innerHTML = rows.join('');
        if (table) table.classList.remove('hidden');
    }

    function dpVerifyVendorLine(tr, btn) {
        if (!tr || !btn || btn.disabled) return;
        var itemCodeEl = tr.querySelector('.dp-h-item-code');
        var skuEl = tr.querySelector('.dp-sku');
        var itemCode = itemCodeEl ? String(itemCodeEl.value || '').trim() : '';
        var sku = skuEl ? String(skuEl.value || '').trim() : '';
        if (!itemCode && !sku) {
            dpShowStatusModal('Select a product from SKU search or enter a SKU linked to an item code before verifying vendor data.', 'warning');
            return;
        }

        var icon = btn.querySelector('i');
        var prevIconClass = icon ? icon.className : 'fas fa-clipboard-check text-xs';
        btn.disabled = true;
        if (icon) icon.className = 'fas fa-spinner fa-spin text-xs';

        var subtitle = itemCode || sku;
        dpOpenVerifyVendorModalLoading(subtitle);

        fetch(verifyVendorLineUrl(tr), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                dpRenderVerifyVendorModal(data || {});
            })
            .catch(function () {
                dpCloseVerifyVendorModal();
                dpShowStatusModal('The vendor verification request failed. Check your connection and try again.', 'error', 'Request failed');
            })
            .finally(function () {
                btn.disabled = false;
                if (icon) icon.className = prevIconClass;
            });
    }

    function dpParseQty(val) {
        var n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function dpIsVendorQtySynced(tr) {
        return tr && String(tr.getAttribute('data-vendor-qty-synced') || '0') === '1';
    }

    function dpUpdateVendorQtySyncButton(tr) {
        if (!tr) return;
        var btn = tr.querySelector('.dp-sync-vendor-qty');
        if (!btn) return;
        var icon = btn.querySelector('i');
        var itemId = parseInt(tr.getAttribute('data-dp-item-id') || '0', 10);
        var synced = dpIsVendorQtySynced(tr);
        var qtyInput = tr.querySelector('.dp-qty');
        var currentQty = qtyInput ? dpParseQty(qtyInput.value) : 0;
        var syncedQty = dpParseQty(tr.getAttribute('data-vendor-qty-synced-qty') || '');

        if (synced && Math.abs(currentQty - syncedQty) > 0.0001) {
            synced = false;
            tr.setAttribute('data-vendor-qty-synced', '0');
        }

        btn.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'border-violet-200', 'bg-violet-50', 'text-violet-800', 'hover:bg-violet-100');
        if (synced) {
            btn.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
            btn.disabled = true;
            btn.title = 'Qty synced to vendor API';
            btn.setAttribute('aria-label', 'Qty synced to vendor API');
            if (icon) icon.className = 'fas fa-check text-xs';
        } else {
            btn.classList.add('border-violet-200', 'bg-violet-50', 'text-violet-800', 'hover:bg-violet-100');
            if (itemId > 0 && DP_PURCHASE_ID > 0) {
                btn.disabled = false;
                btn.title = 'Push qty to vendor API';
                btn.setAttribute('aria-label', 'Push qty to vendor API');
            } else {
                btn.disabled = true;
                btn.title = 'Save purchase first to sync qty';
                btn.setAttribute('aria-label', 'Push qty to vendor API');
            }
            if (icon) icon.className = 'fas fa-cloud-upload-alt text-xs';
        }
    }

    function dpMarkVendorQtyUnsynced(tr) {
        if (!tr) return;
        tr.setAttribute('data-vendor-qty-synced', '0');
        dpUpdateVendorQtySyncButton(tr);
    }

    function dpSyncVendorQty(tr, btn) {
        if (!tr || !btn || btn.disabled) return;
        var itemId = parseInt(tr.getAttribute('data-dp-item-id') || '0', 10);
        if (itemId <= 0 || DP_PURCHASE_ID <= 0) {
            dpShowStatusModal('Save this purchase first before syncing qty to the vendor API.', 'warning');
            return;
        }

        var icon = btn.querySelector('i');
        var prevIconClass = icon ? icon.className : 'fas fa-cloud-upload-alt text-xs';
        btn.disabled = true;
        if (icon) icon.className = 'fas fa-spinner fa-spin text-xs';

        fetch(syncVendorQtyUrl(itemId, DP_PURCHASE_ID), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    dpShowStatusModal((data && data.message) ? data.message : 'Could not sync qty to vendor API.', 'error', 'Vendor qty sync failed');
                    dpUpdateVendorQtySyncButton(tr);
                    return;
                }
                var qtyInput = tr.querySelector('.dp-qty');
                var currentQty = qtyInput ? String(qtyInput.value || '') : '';
                tr.setAttribute('data-vendor-qty-synced', '1');
                tr.setAttribute('data-vendor-qty-synced-qty', currentQty);
                dpUpdateVendorQtySyncButton(tr);
                dpShowStatusModal((data && data.message) ? data.message : 'Qty synced to vendor API.', 'success', 'Qty synced');
            })
            .catch(function () {
                dpShowStatusModal('The vendor qty sync request failed. Check your connection and try again.', 'error', 'Request failed');
                dpUpdateVendorQtySyncButton(tr);
            })
            .finally(function () {
                if (icon && !dpIsVendorQtySynced(tr)) {
                    icon.className = prevIconClass;
                }
            });
    }

    function dpClosePendingOrdersModal() {
        var modal = document.getElementById('dp-pending-orders-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function dpFormatUnixRange(fromTs, toTs) {
        function fmt(ts) {
            if (!ts) return '';
            var d = new Date(ts * 1000);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleDateString();
        }
        return fmt(fromTs) + ' – ' + fmt(toTs);
    }

    function dpEscHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function dpCopyText(text, btn, defaultTitle, failMessage) {
        text = String(text || '').trim();
        if (!text) return;
        defaultTitle = defaultTitle || 'Copy';
        failMessage = failMessage || 'Could not copy to clipboard.';

        function flashCopied() {
            if (!btn) return;
            var icon = btn.querySelector('i');
            if (!icon) return;
            var prev = icon.className;
            icon.className = 'fas fa-check text-xs text-emerald-600';
            btn.setAttribute('title', 'Copied');
            setTimeout(function () {
                icon.className = prev;
                btn.setAttribute('title', defaultTitle);
            }, 1200);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(flashCopied).catch(function () {
                dpShowStatusModal(failMessage, 'warning');
            });
            return;
        }

        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            if (document.execCommand('copy')) {
                flashCopied();
            } else {
                dpShowStatusModal(failMessage, 'warning');
            }
        } catch (err) {
            dpShowStatusModal(failMessage, 'warning');
        }
        document.body.removeChild(ta);
    }

    function dpCopyOrderNumber(orderNumber, btn) {
        dpCopyText(orderNumber, btn, 'Copy order number', 'Could not copy order number to clipboard.');
    }

    function dpCopySku(sku, btn) {
        dpCopyText(sku, btn, 'Copy SKU', 'Could not copy SKU to clipboard.');
    }

    function dpRenderPendingOrdersTable(orders, defaultSku) {
        var tbody = document.getElementById('dp-pending-orders-tbody');
        var table = document.getElementById('dp-pending-orders-table');
        var empty = document.getElementById('dp-pending-orders-empty');
        if (!tbody || !table || !empty) return;

        tbody.innerHTML = '';
        if (!orders || !orders.length) {
            table.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');
        table.classList.remove('hidden');

        orders.forEach(function (row, idx) {
            var tr = document.createElement('tr');
            tr.className = row.needs_import ? 'bg-amber-50/60' : '';
            tr.setAttribute('data-order-number', row.order_number || '');
            tr.setAttribute('data-row-index', String(idx));

            var orderNo = row.order_number || '';
            var lineSku = row.sku || defaultSku || '';
            var qtyText = row.qty != null && row.qty !== '' ? row.qty : '—';

            tr.innerHTML =
                '<td class="py-2.5 pr-3">' +
                    '<div class="flex items-center gap-2">' +
                        '<span class="font-medium text-gray-900 tabular-nums">' + dpEscHtml(orderNo) + '</span>' +
                        '<button type="button" class="dp-po-copy-order inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500" title="Copy order number" aria-label="Copy order number ' + dpEscHtml(orderNo) + '" data-order-number="' + dpEscHtml(orderNo) + '">' +
                            '<i class="fas fa-copy text-xs" aria-hidden="true"></i>' +
                        '</button>' +
                    '</div>' +
                '</td>' +
                '<td class="py-2.5 pr-3">' +
                    '<div class="flex items-center gap-2">' +
                        '<span class="font-medium text-gray-900 tabular-nums">' + dpEscHtml(lineSku) + '</span>' +
                        (lineSku ? (
                            '<button type="button" class="dp-po-copy-sku inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500" title="Copy SKU" aria-label="Copy SKU ' + dpEscHtml(lineSku) + '" data-sku="' + dpEscHtml(lineSku) + '">' +
                                '<i class="fas fa-copy text-xs" aria-hidden="true"></i>' +
                            '</button>'
                        ) : '') +
                    '</div>' +
                '</td>' +
                '<td class="py-2.5 text-right tabular-nums text-gray-900">' + dpEscHtml(qtyText) + '</td>';

            tbody.appendChild(tr);
        });
    }

    function dpUpdatePendingOrderRow(tr, patch) {
        if (!tr || !patch) return;
        if (patch.match_status === 'matched') {
            tr.classList.remove('bg-amber-50/60');
        }
    }

    function dpImportPendingOrdersSequential(orders) {
        var toImport = (orders || []).filter(function (row) { return row && row.needs_import; });
        var statusEl = document.getElementById('dp-pending-orders-import-status');
        if (!toImport.length) {
            if (statusEl) {
                statusEl.textContent = 'All listed orders are already in the system.';
                statusEl.classList.remove('hidden');
            }
            return Promise.resolve();
        }

        if (statusEl) {
            statusEl.textContent = 'Importing ' + toImport.length + ' order(s) in background…';
            statusEl.classList.remove('hidden');
        }

        var chain = Promise.resolve();
        toImport.forEach(function (row, i) {
            chain = chain.then(function () {
                var tbody = document.getElementById('dp-pending-orders-tbody');
                var tr = tbody ? tbody.querySelector('tr[data-order-number="' + CSS.escape(row.order_number) + '"]') : null;
                if (statusEl) {
                    statusEl.textContent = 'Importing order ' + (i + 1) + ' of ' + toImport.length + '…';
                }
                return fetch(importOrderUrl(row.order_number), {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var ok = data && data.success;
                        if (tr && ok) {
                            dpUpdatePendingOrderRow(tr, { match_status: 'matched' });
                        }
                    })
                    .catch(function () { /* keep row highlighted on failure */ });
            });
        });

        return chain.then(function () {
            if (statusEl) {
                statusEl.textContent = 'Background import finished.';
            }
        });
    }

    function initDpPendingOrdersModal() {
        var modal = document.getElementById('dp-pending-orders-modal');
        if (!modal) return;
        var backdrop = document.getElementById('dp-pending-orders-backdrop');
        var closeBtn = document.getElementById('dp-pending-orders-close');
        var tbody = document.getElementById('dp-pending-orders-tbody');
        if (backdrop) backdrop.addEventListener('click', dpClosePendingOrdersModal);
        if (closeBtn) closeBtn.addEventListener('click', dpClosePendingOrdersModal);
        if (tbody) {
            tbody.addEventListener('click', function (e) {
                var orderBtn = e.target.closest('.dp-po-copy-order');
                if (orderBtn) {
                    e.preventDefault();
                    dpCopyOrderNumber(orderBtn.getAttribute('data-order-number') || '', orderBtn);
                    return;
                }
                var skuBtn = e.target.closest('.dp-po-copy-sku');
                if (skuBtn) {
                    e.preventDefault();
                    dpCopySku(skuBtn.getAttribute('data-sku') || '', skuBtn);
                }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                dpClosePendingOrdersModal();
            }
        });
    }

    function initDpVerifyVendorModal() {
        var modal = document.getElementById('dp-verify-vendor-modal');
        if (!modal) return;
        var backdrop = document.getElementById('dp-verify-vendor-backdrop');
        var closeBtn = document.getElementById('dp-verify-vendor-close');
        if (backdrop) backdrop.addEventListener('click', dpCloseVerifyVendorModal);
        if (closeBtn) closeBtn.addEventListener('click', dpCloseVerifyVendorModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                dpCloseVerifyVendorModal();
            }
        });
    }

    function dpFetchPendingOrders(tr, btn) {
        if (!tr || !btn || btn.disabled) return;

        var cell = tr.querySelector('.dp-sku-cell');
        var itemCode = cell && cell.querySelector('.dp-h-item-code') ? String(cell.querySelector('.dp-h-item-code').value || '').trim() : '';
        var sku = cell && cell.querySelector('.dp-sku') ? String(cell.querySelector('.dp-sku').value || '').trim() : '';
        var color = cell && cell.querySelector('.dp-h-color') ? String(cell.querySelector('.dp-h-color').value || '').trim() : '';
        var size = cell && cell.querySelector('.dp-h-size') ? String(cell.querySelector('.dp-h-size').value || '').trim() : '';
        if (!itemCode && !sku) {
            dpShowStatusModal('Select a product from SKU search or enter a SKU linked to an item code before fetching pending orders.', 'warning');
            return;
        }

        var modal = document.getElementById('dp-pending-orders-modal');
        var loading = document.getElementById('dp-pending-orders-loading');
        var table = document.getElementById('dp-pending-orders-table');
        var empty = document.getElementById('dp-pending-orders-empty');
        var subtitle = document.getElementById('dp-pending-orders-subtitle');
        var statusEl = document.getElementById('dp-pending-orders-import-status');
        var title = document.getElementById('dp-pending-orders-title');

        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        if (loading) loading.classList.remove('hidden');
        if (table) table.classList.add('hidden');
        if (empty) empty.classList.add('hidden');
        if (statusEl) {
            statusEl.textContent = '';
            statusEl.classList.add('hidden');
        }
        if (title) {
            title.textContent = sku ? ('Pending orders — ' + sku) : 'Pending orders for SKU';
        }
        if (subtitle) {
            subtitle.textContent = 'Loading…';
        }

        var icon = btn.querySelector('i');
        var prevIconClass = icon ? icon.className : 'fas fa-clipboard-list text-xs';
        btn.disabled = true;
        if (icon) icon.className = 'fas fa-spinner fa-spin text-xs';

        fetch(fetchPendingOrdersUrl(itemCode, sku, color, size), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (loading) loading.classList.add('hidden');
                if (!data || !data.success) {
                    dpClosePendingOrdersModal();
                    dpShowStatusModal((data && data.message) ? data.message : 'Could not fetch pending orders.', 'error');
                    return;
                }
                if (subtitle) {
                    var range = dpFormatUnixRange(data.from_date, data.to_date);
                    var parts = [];
                    if (data.item_code) parts.push('Item ' + data.item_code);
                    if (range) parts.push(range);
                    if (data.total != null) parts.push(data.total + ' order(s)');
                    subtitle.textContent = parts.join(' · ');
                }
                dpRenderPendingOrdersTable(data.orders || [], data.sku || sku);
                dpImportPendingOrdersSequential(data.orders || []);
            })
            .catch(function () {
                if (loading) loading.classList.add('hidden');
                dpClosePendingOrdersModal();
                dpShowStatusModal('The pending orders request failed. Check your connection and try again.', 'error', 'Request failed');
            })
            .finally(function () {
                btn.disabled = false;
                if (icon) icon.className = prevIconClass;
            });
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
            if (el) el.addEventListener('input', function () {
                recalcRow(tr);
                if (sel === '.dp-qty') {
                    dpMarkVendorQtyUnsynced(tr);
                }
            });
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
        var fetchBtn = tr.querySelector('.dp-fetch-price');
        if (fetchBtn) {
            fetchBtn.addEventListener('click', function () {
                dpFetchLatestPrice(tr, fetchBtn);
            });
        }
        var pendingBtn = tr.querySelector('.dp-fetch-pending-orders');
        if (pendingBtn) {
            pendingBtn.addEventListener('click', function () {
                dpFetchPendingOrders(tr, pendingBtn);
            });
        }
        var qtySyncBtn = tr.querySelector('.dp-sync-vendor-qty');
        if (qtySyncBtn) {
            qtySyncBtn.addEventListener('click', function () {
                dpSyncVendorQty(tr, qtySyncBtn);
            });
        }
        var verifyBtn = tr.querySelector('.dp-verify-vendor');
        if (verifyBtn) {
            verifyBtn.addEventListener('click', function () {
                dpVerifyVendorLine(tr, verifyBtn);
            });
        }
        dpUpdateVendorQtySyncButton(tr);
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
    initDpStatusModal();
    initDpPendingOrdersModal();
    initDpVerifyVendorModal();
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
