<?php
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$rfmt = static function ($n, int $dec = 2): string {
    return number_format((float)$n, $dec, '.', ',');
};

$inv = $invoice ?? [];
$itemsList = $items ?? [];
$intl = $internationalData ?? [];
$orderInfo = $order_info ?? [];
$firmData = $firm ?? [];

$invoiceId = (int)($inv['id'] ?? 0);
$invoiceNumber = $inv['invoice_number'] ?? '';
$invoiceDate = !empty($inv['invoice_date']) ? date('d M Y', strtotime($inv['invoice_date'])) : date('d M Y');
$currency = $inv['currency'] ?? 'INR';
$currencyPrefix = $currency === 'INR' ? '₹' : $currency . ' ';

$irn = trim((string)($intl['irn'] ?? $inv['irn'] ?? ''));
$irnStatus = strtolower(trim((string)($intl['irn_status'] ?? $inv['irn_status'] ?? '')));
$isIrnGenerated = (!empty($irn) && ($irnStatus === 'generated' || $irnStatus === 'act' || $irnStatus === 'active'));

$ewbNo = trim((string)($intl['ewb_no'] ?? $intl['ewb'] ?? $inv['ewb_number'] ?? ''));
$ewbStatus = strtolower(trim((string)($intl['ewb_status'] ?? $inv['ewb_status'] ?? '')));
$isEwbGenerated = (!empty($ewbNo) && $ewbNo !== '0');

$isExport = ($currency !== 'INR') || (!empty($orderInfo['country']) && strtoupper(trim($orderInfo['country'])) !== 'IN');

$einvoiceInputUrl = base_url('?page=invoices&action=einvoice-input&id=' . $invoiceId);
$ewaybillInputUrl = base_url('?page=invoices&action=ewaybill-input&id=' . $invoiceId);
$invoiceListUrl = base_url('?page=invoices&action=list');

$flashNotice = $_SESSION['flash_notice_message'] ?? null;
if (isset($_SESSION['flash_notice_message'])) {
    unset($_SESSION['flash_notice_message']);
}
if ($flashNotice === null && isset($_GET['already_created']) && $_GET['already_created'] == '1') {
    $flashNotice = 'Invoice already created for this order.';
}
?>

<div class="mx-auto max-w-[1300px] space-y-6 px-3 py-6 md:px-6 font-sans text-slate-800">

    <?php if ($flashNotice): ?>
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-800 font-bold">
                        <i class="fas fa-info-circle text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-sky-950">Invoice Already Created</h3>
                        <p class="text-sm text-sky-800 mt-0.5"><?= $h($flashNotice) ?></p>
                    </div>
                </div>
                <?php if (!$isIrnGenerated): ?>
                    <div class="shrink-0">
                        <a href="<?= $h($einvoiceInputUrl) ?>" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-orange-700 shadow-md transition">
                            <i class="fas fa-file-signature"></i>
                            <span>Generate IRN</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Top Action Bar -->
    <div class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:flex-row md:items-center md:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-500 text-white shadow-md">
                <i class="fas fa-file-invoice text-xl" aria-hidden="true"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-slate-800">Invoice #<?= $h($invoiceNumber) ?></h1>
                    <?php if ($isExport): ?>
                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-800 border border-sky-200">Export / International</span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">Domestic</span>
                    <?php endif; ?>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700"><?= $h($currency) ?></span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Generated on <?= $h($invoiceDate) ?> &bull; Status: <span class="font-semibold text-slate-700 uppercase"><?= $h($inv['status'] ?? 'final') ?></span></p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= $h($invoiceListUrl) ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                <i class="fas fa-arrow-left"></i> Invoices List
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 py-2.5 text-sm font-semibold text-orange-800 hover:bg-orange-100 shadow-sm transition">
                <i class="fas fa-print"></i> Print Invoice
            </button>
        </div>
    </div>

    <!-- Compliance Workflow Card (IRN / E-Way Bill) -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-shield-alt text-orange-500"></i>
                Compliance &amp; GST E-Invoicing
            </h2>
            <span class="text-xs font-medium text-slate-500">Step-by-step IRN &amp; E-Way Bill flow</span>
        </div>

        <!-- STAGE 1: IRN STATUS -->
        <?php if (!$isIrnGenerated): ?>
            <!-- IRN PENDING STATE -->
            <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 font-bold">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-amber-900">E-Invoice (IRN) Generation Required</h3>
                            <p class="text-sm text-amber-800 mt-0.5">The tax invoice is created. Review and collect tax/export details to generate the Government IRN.</p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <a href="<?= $h($einvoiceInputUrl) ?>" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white hover:bg-orange-700 shadow-md transition">
                            <i class="fas fa-file-signature"></i>
                            <span>Generate IRN</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- IRN GENERATED STATE -->
            <div class="rounded-xl border border-emerald-300 bg-emerald-50/80 p-5 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-200 text-emerald-800 font-bold">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                        <div>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 uppercase tracking-wide">E-Invoice Active</span>
                            <h3 class="text-base font-bold text-emerald-950 mt-1">IRN Generated Successfully</h3>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-xl bg-white/80 p-4 border border-emerald-200 text-xs font-mono">
                    <div>
                        <span class="block text-[11px] font-sans font-semibold text-emerald-800">IRN:</span>
                        <span class="break-all font-bold text-emerald-950"><?= $h($irn) ?></span>
                    </div>
                    <?php if (!empty($intl['ack_number'])): ?>
                        <div>
                            <span class="block text-[11px] font-sans font-semibold text-emerald-800">Ack No:</span>
                            <span class="font-bold text-emerald-950"><?= $h($intl['ack_number']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($intl['ack_date'])): ?>
                        <div>
                            <span class="block text-[11px] font-sans font-semibold text-emerald-800">Ack Date:</span>
                            <span class="font-bold text-emerald-950"><?= $h($intl['ack_date']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- STAGE 2: E-WAY BILL STATUS (ONLY WHEN IRN IS GENERATED) -->
                <div class="pt-3 border-t border-emerald-200/60">
                    <?php if (!$isEwbGenerated): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fas fa-truck text-emerald-600"></i> E-Way Bill (EWB) Pending
                                </h4>
                                <p class="text-xs text-slate-600 mt-0.5">Proceed to collect transporter &amp; vehicle info for GST E-Way Bill generation.</p>
                            </div>
                            <a href="<?= $h($ewaybillInputUrl) ?>" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 shadow-md transition shrink-0">
                                <i class="fas fa-shipping-fast"></i>
                                <span>Generate E-Way Bill</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- E-WAY BILL CONFIRMATION -->
                        <div class="rounded-xl border border-emerald-400 bg-white p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                                    <i class="fas fa-box-check text-emerald-600"></i> E-Way Bill Confirmation
                                </h4>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Confirmed</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs font-mono bg-slate-50 p-3 rounded-lg border border-slate-200">
                                <div><span class="font-sans font-semibold text-slate-600">EWB No:</span> <span class="font-bold text-slate-900"><?= $h($ewbNo) ?></span></div>
                                <?php if (!empty($intl['ewb_date'])): ?><div><span class="font-sans font-semibold text-slate-600">Date:</span> <?= $h($intl['ewb_date']) ?></div><?php endif; ?>
                                <?php if (!empty($intl['ewb_valid_till'])): ?><div><span class="font-sans font-semibold text-slate-600">Valid Till:</span> <?= $h($intl['ewb_valid_till']) ?></div><?php endif; ?>
                            </div>
                            <?php if (!empty($intl['trans_name']) || !empty($intl['veh_no']) || !empty($intl['trans_id'])): ?>
                                <div class="text-xs text-slate-600 space-x-3">
                                    <?php if (!empty($intl['trans_name'])): ?><span><strong>Transporter:</strong> <?= $h($intl['trans_name']) ?></span><?php endif; ?>
                                    <?php if (!empty($intl['trans_id'])): ?><span><strong>Trans ID:</strong> <?= $h($intl['trans_id']) ?></span><?php endif; ?>
                                    <?php if (!empty($intl['veh_no'])): ?><span><strong>Vehicle No:</strong> <?= $h($intl['veh_no']) ?></span><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <!-- Main Invoice Preview Box -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        
        <!-- Seller & Customer Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 border-b border-slate-200 bg-slate-50/50">
            <!-- Seller Info -->
            <div class="p-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Seller / Billed From</h3>
                <h4 class="text-base font-bold text-slate-900"><?= $h($firmData['firm_name'] ?? 'Exotic India Art') ?></h4>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= nl2br($h($firmData['address'] ?? 'New Delhi')) ?></p>
                <?php if (!empty($firmData['gstin'])): ?>
                    <p class="text-xs font-semibold text-slate-700 mt-2">GSTIN: <span class="font-mono"><?= $h($firmData['gstin']) ?></span></p>
                <?php endif; ?>
            </div>

            <!-- Buyer Info -->
            <div class="p-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Buyer / Bill To</h3>
                <?php
                $buyerName = trim(($orderInfo['first_name'] ?? '') . ' ' . ($orderInfo['last_name'] ?? ''));
                if ($buyerName === '') {
                    $buyerName = trim((string)($orderInfo['name'] ?? 'Customer'));
                }
                $billAddr = trim(($orderInfo['address_line1'] ?? '') . ' ' . ($orderInfo['address_line2'] ?? '') . ', ' . ($orderInfo['city'] ?? '') . ' ' . ($orderInfo['state'] ?? '') . ' ' . ($orderInfo['zipcode'] ?? '') . ' ' . ($orderInfo['country'] ?? ''));
                ?>
                <h4 class="text-base font-bold text-slate-900"><?= $h($buyerName) ?></h4>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= $h($billAddr) ?></p>
                <?php if (!empty($orderInfo['gstin'])): ?>
                    <p class="text-xs font-semibold text-slate-700 mt-2">GSTIN: <span class="font-mono"><?= $h($orderInfo['gstin']) ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Export Route Details (if International) -->
        <?php if ($isExport && !empty($intl)): ?>
            <div class="p-6 border-b border-slate-200 bg-sky-50/40">
                <h3 class="text-xs font-bold uppercase tracking-wider text-sky-900 mb-3 flex items-center gap-2">
                    <i class="fas fa-plane-departure text-sky-600"></i> Export &amp; Shipment Details
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="block text-slate-500 font-medium">Pre-Carriage By:</span>
                        <span class="font-semibold text-slate-800"><?= $h($intl['pre_carriage_by'] ?? 'Air') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Port of Loading:</span>
                        <span class="font-semibold text-slate-800"><?= $h($intl['port_of_loading'] ?? 'INABG1') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Port of Discharge:</span>
                        <span class="font-semibold text-slate-800"><?= $h($intl['port_of_discharge'] ?? '—') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Final Destination:</span>
                        <span class="font-semibold text-slate-800"><?= $h($intl['final_destination'] ?? '—') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Shipping Bill No:</span>
                        <span class="font-mono font-semibold text-slate-800"><?= $h($intl['shipping_bill_number'] ?? '—') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Shipping Bill Date:</span>
                        <span class="font-semibold text-slate-800"><?= $h($intl['shipping_bill_date'] ?? '—') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Port Code:</span>
                        <span class="font-mono font-semibold text-slate-800"><?= $h($intl['shipping_port'] ?? $intl['port_code'] ?? 'INABG1') ?></span>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">USD Export Rate:</span>
                        <span class="font-mono font-semibold text-slate-800"><?= $rfmt($intl['usd_export_rate'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Line Items Table -->
        <div class="p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Line Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 uppercase font-semibold">
                            <th class="p-3 border-b border-slate-200">#</th>
                            <th class="p-3 border-b border-slate-200">Order #</th>
                            <th class="p-3 border-b border-slate-200">Item Code / SKU</th>
                            <th class="p-3 border-b border-slate-200">Description</th>
                            <th class="p-3 border-b border-slate-200">HSN</th>
                            <th class="p-3 border-b border-slate-200 text-right">Qty</th>
                            <th class="p-3 border-b border-slate-200 text-right">Unit Price</th>
                            <th class="p-3 border-b border-slate-200 text-right">Tax Rate</th>
                            <th class="p-3 border-b border-slate-200 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($itemsList as $idx => $item): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-medium text-slate-500"><?= $idx + 1 ?></td>
                                <td class="p-3 font-mono font-medium text-slate-800"><?= $h($item['order_number'] ?? '') ?></td>
                                <td class="p-3 font-mono font-medium text-slate-800"><?= $h($item['item_code'] ?? '') ?></td>
                                <td class="p-3 font-medium text-slate-900"><?= $h($item['item_name'] ?? $item['title'] ?? '') ?></td>
                                <td class="p-3 font-mono text-slate-600"><?= $h($item['hsn'] ?? '') ?></td>
                                <td class="p-3 text-right font-mono"><?= (int)($item['quantity'] ?? 1) ?></td>
                                <td class="p-3 text-right font-mono"><?= $currencyPrefix ?><?= $rfmt($item['unit_price'] ?? 0) ?></td>
                                <td class="p-3 text-right font-mono"><?= (float)($item['tax_rate'] ?? 0) ?>%</td>
                                <td class="p-3 text-right font-bold font-mono text-slate-900"><?= $currencyPrefix ?><?= $rfmt($item['line_total'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Invoice Totals Summary -->
        <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end">
            <div class="w-full max-w-sm space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono font-medium text-slate-800"><?= $currencyPrefix ?><?= $rfmt($inv['subtotal'] ?? 0) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Tax Amount:</span>
                    <span class="font-mono font-medium text-slate-800"><?= $currencyPrefix ?><?= $rfmt($inv['tax_amount'] ?? 0) ?></span>
                </div>
                <?php if ((float)($inv['discount_amount'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-slate-600">
                        <span>Discount:</span>
                        <span class="font-mono font-medium text-slate-800">- <?= $currencyPrefix ?><?= $rfmt($inv['discount_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between pt-3 border-t border-slate-300 text-base font-bold text-slate-900">
                    <span>Total Amount (<?= $h($currency) ?>):</span>
                    <span class="font-mono text-orange-600"><?= $currencyPrefix ?><?= $rfmt($inv['total_amount'] ?? 0) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>
