<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
$items = $data['items'] ?? [];

$irn = $common['irn'] ?? $data['irn'] ?? ($common['inv_irn'] ?? '');
$ackNumber = $common['ack_number'] ?? $data['ack_number'] ?? ($common['ack_no'] ?? '');
$ackDate = $common['ack_date'] ?? $data['ack_date'] ?? '';
$qrcodeString = $common['qrcode_string'] ?? $data['qrcode_string'] ?? '';

$supplyType = $data['supply_type'] ?? 'SUPPLY MEANT FOR EXPORT WITH PAYMENT OF IGST';
$currency = $common['currency'] ?? 'USD';
$exchangeRate = (float)($common['exchange_rate'] ?? 83.50);
?>
<!-- QRCode library for IRN QR rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="export-doc-page commercial-invoice">
    <!-- Header with IRN QR Code and Supply Clause -->
    <div class="border-b-2 border-black pb-3 mb-3">
        <div class="flex justify-between items-start">
            <div class="space-y-1">
                <h1 class="text-lg font-extrabold uppercase tracking-wider text-black">
                    <?= htmlspecialchars($data['document_title'] ?? 'TAX INVOICE / COMMERCIAL INVOICE CUM PACKING LIST') ?>
                </h1>
                <div class="inline-block bg-black text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">
                    <?= htmlspecialchars($supplyType) ?>
                </div>
            </div>

            <!-- IRN QR Code Section -->
            <div class="flex items-center gap-3">
                <?php if (!empty($qrcodeString)): ?>
                    <div id="irnQrContainer" class="border border-black p-1 bg-white" style="width: 90px; height: 90px;"></div>
                <?php else: ?>
                    <div class="border border-dashed border-gray-400 p-2 text-[9px] text-gray-500 text-center flex items-center justify-center" style="width: 90px; height: 90px;">
                        [IRN QR Code]
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- IRN Details Bar -->
        <?php if (!empty($irn)): ?>
            <div class="mt-2 p-1.5 bg-gray-50 border border-black text-[10px] space-y-0.5">
                <div><strong>IRN:</strong> <span class="font-mono break-all"><?= htmlspecialchars($irn) ?></span></div>
                <div class="flex gap-4">
                    <div><strong>Ack No:</strong> <?= htmlspecialchars($ackNumber ?: 'N/A') ?></div>
                    <div><strong>Ack Date:</strong> <?= htmlspecialchars($ackDate ? date('d-m-Y H:i', strtotime($ackDate)) : 'N/A') ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Header Metadata Bar -->
    <div class="grid grid-cols-4 gap-2 border border-black p-2 mb-3 text-[11px] bg-gray-50">
        <div><strong>Invoice No:</strong><br><?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></div>
        <div><strong>Invoice Date:</strong><br><?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
        <div><strong>Order Ref:</strong><br><?= htmlspecialchars($data['buyer_order_ref'] ?? $common['order_number'] ?? 'N/A') ?></div>
        <div><strong>Currency / Exchange:</strong><br><?= htmlspecialchars($currency) ?> @ ₹<?= number_format($exchangeRate, 2) ?></div>
    </div>

    <!-- Parties Grid (Exporter & Consignee) -->
    <div class="grid grid-cols-2 gap-3 border border-black p-3 mb-3 text-xs">
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase text-[11px]">Exporter / Manufacturer (Shipper)</h3>
            <div><strong><?= htmlspecialchars($common['exporter_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['exporter_address'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['exporter_city'] ?? '') ?> - <?= htmlspecialchars($common['exporter_pincode'] ?? '') ?></div>
            <div><strong>IEC No:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '') ?></div>
            <div><strong>GSTIN:</strong> <?= htmlspecialchars($common['exporter_gstin'] ?? '07AADCE1400C1ZJ') ?></div>
            <div><strong>PAN:</strong> <?= htmlspecialchars($common['exporter_pan'] ?? 'AADCE1400C') ?></div>
        </div>
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase text-[11px]">Consignee / Buyer (Shipping Address)</h3>
            <div><strong><?= htmlspecialchars($common['consignee_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
            <?php if (!empty($common['consignee_address_line2'])): ?>
                <div><?= htmlspecialchars($common['consignee_address_line2']) ?></div>
            <?php endif; ?>
            <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_state'] ?? '') ?> <?= htmlspecialchars($common['consignee_zipcode'] ?? '') ?></div>
            <div><strong>Country:</strong> <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
            <div><strong>Phone / Email:</strong> <?= htmlspecialchars($common['consignee_phone'] ?? 'N/A') ?> / <?= htmlspecialchars($common['consignee_email'] ?? 'N/A') ?></div>
        </div>
    </div>

    <!-- Logistics Details -->
    <div class="grid grid-cols-4 gap-2 border border-black p-2 mb-3 text-[11px] bg-gray-50">
        <div><strong>Port of Loading:</strong><br><?= htmlspecialchars($common['port_of_loading'] ?? 'INABG1') ?></div>
        <div><strong>Port of Discharge:</strong><br><?= htmlspecialchars($common['port_of_discharge'] ?? 'N/A') ?></div>
        <div><strong>Gross / Net Wt:</strong><br><?= htmlspecialchars($common['gross_weight'] ?? '0.50') ?> / <?= htmlspecialchars($common['net_weight'] ?? '0.40') ?> KG</div>
        <div><strong>Total Packages:</strong><br><?= htmlspecialchars($common['total_packages'] ?? 1) ?> Box(es)</div>
    </div>

    <!-- Items & Packing List Table -->
    <table class="w-full text-left text-xs border-collapse border border-black mb-3">
        <thead>
            <tr class="bg-gray-100 border-b border-black font-bold text-[11px]">
                <th class="border border-black p-1.5 text-center w-8">S.N.</th>
                <th class="border border-black p-1.5 w-24">Item Code</th>
                <th class="border border-black p-1.5">Description of Goods</th>
                <th class="border border-black p-1.5 text-center w-20">HSN Code</th>
                <th class="border border-black p-1.5 text-center w-12">Qty</th>
                <th class="border border-black p-1.5 text-right w-20">Rate (<?= htmlspecialchars($currency) ?>)</th>
                <th class="border border-black p-1.5 text-right w-24">Value (<?= htmlspecialchars($currency) ?>)</th>
                <th class="border border-black p-1.5 text-right w-24">Value (INR)</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalQty = 0; $totalValCurr = 0.0; $totalValInr = 0.0; ?>
            <?php foreach ($items as $idx => $it): ?>
                <?php 
                    $qty = (int)($it['quantity'] ?? 1);
                    $rateCurr = (float)($it['unit_price'] ?? 0);
                    $amtCurr = (float)($it['amount'] ?? ($qty * $rateCurr));
                    $amtInr = $amtCurr * $exchangeRate;

                    $totalQty += $qty;
                    $totalValCurr += $amtCurr;
                    $totalValInr += $amtInr;
                ?>
                <tr>
                    <td class="border border-black p-1.5 text-center"><?= $idx + 1 ?></td>
                    <td class="border border-black p-1.5 font-mono text-[11px]"><?= htmlspecialchars($it['item_code'] ?? '') ?></td>
                    <td class="border border-black p-1.5"><?= htmlspecialchars($it['description'] ?? '') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= htmlspecialchars($it['hsn_code'] ?? '') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= $qty ?></td>
                    <td class="border border-black p-1.5 text-right"><?= number_format($rateCurr, 2) ?></td>
                    <td class="border border-black p-1.5 text-right font-semibold"><?= number_format($amtCurr, 2) ?></td>
                    <td class="border border-black p-1.5 text-right font-semibold">₹<?= number_format($amtInr, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="font-bold bg-gray-100 border-t-2 border-black">
                <td colspan="4" class="border border-black p-1.5 text-right uppercase">Total:</td>
                <td class="border border-black p-1.5 text-center"><?= $totalQty ?></td>
                <td class="border border-black p-1.5"></td>
                <td class="border border-black p-1.5 text-right"><?= htmlspecialchars($currency) ?> <?= number_format($totalValCurr, 2) ?></td>
                <td class="border border-black p-1.5 text-right">₹<?= number_format($totalValInr, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Tax & IGST Calculation Box for "WITH PAYMENT OF IGST" -->
    <?php
        $igstRate = (float)($data['igst_rate'] ?? 18.0);
        $igstAmtInr = $totalValInr * ($igstRate / 100);
        $grandTotalInr = $totalValInr + $igstAmtInr;
    ?>
    <div class="border border-black p-2.5 mb-3 text-xs bg-gray-50/50 space-y-1">
        <div class="font-bold uppercase text-[11px] border-b border-gray-300 pb-1 mb-1">IGST & Export Tax Details (In Indian Rupees - INR)</div>
        <div class="grid grid-cols-3 gap-2">
            <div>Taxable Value (INR): <strong>₹<?= number_format($totalValInr, 2) ?></strong></div>
            <div>Integrated Tax (IGST @ <?= $igstRate ?>%): <strong>₹<?= number_format($igstAmtInr, 2) ?></strong></div>
            <div>Grand Total incl. IGST (INR): <strong>₹<?= number_format($grandTotalInr, 2) ?></strong></div>
        </div>
    </div>

    <!-- Declaration Clause -->
    <div class="border border-black p-3 space-y-3 text-xs">
        <div>
            <strong class="uppercase block text-[10px] text-gray-700">Export Declaration & IGST Payment Statement:</strong>
            <p class="italic text-[11px] text-gray-900 leading-relaxed"><?= htmlspecialchars($data['declaration_clause'] ?? 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct. SUPPLY MEANT FOR EXPORT WITH PAYMENT OF INTEGRATED TAX (IGST).') ?></p>
        </div>
        <div class="pt-4 flex justify-between items-end">
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

<?php if (!empty($qrcodeString)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('irnQrContainer');
    if (container && typeof QRCode !== 'undefined') {
        container.innerHTML = '';
        new QRCode(container, {
            text: <?= json_encode($qrcodeString) ?>,
            width: 80,
            height: 80,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
<?php endif; ?>
