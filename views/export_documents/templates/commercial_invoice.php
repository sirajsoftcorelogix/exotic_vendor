<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
$items = $data['items'] ?? [];
?>
<div class="export-doc-page commercial-invoice">
    <div class="doc-header flex justify-between items-start border-b-2 border-black pb-3 mb-4">
        <div>
            <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'COMMERCIAL INVOICE') ?></h1>
            <p class="text-xs text-gray-700">Formal Export & Customs Clearance Document</p>
        </div>
        <div class="text-right text-xs">
            <div><strong>Invoice No:</strong> <?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></div>
            <div><strong>Invoice Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
            <div><strong>Order Ref:</strong> <?= htmlspecialchars($data['buyer_order_ref'] ?? $common['order_number'] ?? 'N/A') ?></div>
            <div><strong>LUT No:</strong> <?= htmlspecialchars($data['lut_number'] ?? 'AD070324001234X') ?></div>
        </div>
    </div>

    <!-- Parties Grid -->
    <div class="grid grid-cols-2 gap-4 border border-black p-3 mb-4 text-xs">
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Exporter / Manufacturer</h3>
            <div><strong><?= htmlspecialchars($common['exporter_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['exporter_address'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['exporter_city'] ?? '') ?> - <?= htmlspecialchars($common['exporter_pincode'] ?? '') ?></div>
            <div><strong>IEC No:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '') ?></div>
            <div><strong>GSTIN:</strong> <?= htmlspecialchars($common['exporter_gstin'] ?? '07AABCE1234F1Z5') ?></div>
        </div>
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Consignee (Buyer)</h3>
            <div><strong><?= htmlspecialchars($common['consignee_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
            <div><strong>Phone:</strong> <?= htmlspecialchars($common['consignee_phone'] ?? 'N/A') ?></div>
            <div><strong>Terms of Payment:</strong> <?= htmlspecialchars($data['terms_of_payment'] ?? 'Prepaid Advance') ?></div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="w-full text-left text-xs border-collapse border border-black mb-4">
        <thead>
            <tr class="bg-gray-100 border-b border-black font-bold">
                <th class="border border-black p-1.5 text-center w-10">S.N.</th>
                <th class="border border-black p-1.5 w-28">Item Code</th>
                <th class="border border-black p-1.5">Description of Goods</th>
                <th class="border border-black p-1.5 text-center w-24">HSN Code</th>
                <th class="border border-black p-1.5 text-center w-14">Qty</th>
                <th class="border border-black p-1.5 text-right w-24">Rate (<?= htmlspecialchars($common['currency'] ?? 'USD') ?>)</th>
                <th class="border border-black p-1.5 text-right w-28">Amount (<?= htmlspecialchars($common['currency'] ?? 'USD') ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalQty = 0; $totalVal = 0; ?>
            <?php foreach ($items as $idx => $it): ?>
                <?php 
                    $qty = (int)($it['quantity'] ?? 1);
                    $rate = (float)($it['unit_price'] ?? 0);
                    $amt = (float)($it['amount'] ?? ($qty * $rate));
                    $totalQty += $qty;
                    $totalVal += $amt;
                ?>
                <tr>
                    <td class="border border-black p-1.5 text-center"><?= $idx + 1 ?></td>
                    <td class="border border-black p-1.5 font-mono"><?= htmlspecialchars($it['item_code'] ?? '') ?></td>
                    <td class="border border-black p-1.5"><?= htmlspecialchars($it['description'] ?? '') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= htmlspecialchars($it['hsn_code'] ?? '') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= $qty ?></td>
                    <td class="border border-black p-1.5 text-right"><?= number_format($rate, 2) ?></td>
                    <td class="border border-black p-1.5 text-right font-semibold"><?= number_format($amt, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="font-bold bg-gray-100 border-t-2 border-black">
                <td colspan="4" class="border border-black p-1.5 text-right uppercase">Total:</td>
                <td class="border border-black p-1.5 text-center"><?= $totalQty ?></td>
                <td class="border border-black p-1.5"></td>
                <td class="border border-black p-1.5 text-right"><?= htmlspecialchars($common['currency'] ?? 'USD') ?> <?= number_format($totalVal, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Declaration Clause -->
    <div class="border border-black p-3 space-y-3 text-xs">
        <div>
            <strong class="uppercase block text-[10px] text-gray-700">Export Declaration & LUT Clause:</strong>
            <p class="italic text-[11px] text-gray-900"><?= htmlspecialchars($data['declaration_clause'] ?? 'We declare that this invoice shows the actual price of the goods described. Export under Letter of Undertaking (LUT) without payment of IGST.') ?></p>
        </div>
        <div class="pt-6 flex justify-between items-end">
            <div>
                <div><strong>Place:</strong> <?= htmlspecialchars($common['exporter_city'] ?? 'New Delhi') ?></div>
                <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
            </div>
            <div class="text-right">
                <div class="font-bold uppercase mb-8">For <?= htmlspecialchars($common['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
                <div class="border-t border-black pt-1 px-4 inline-block font-semibold">Authorized Signatory</div>
            </div>
        </div>
    </div>
</div>
