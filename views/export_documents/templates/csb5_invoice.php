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
?>
<!-- QRCode library for IRN QR rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="export-doc-page csb5-invoice">
    <div class="doc-header border-b-2 border-black pb-3 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'EXPRESS COURIER INVOICE (CSB-5)') ?></h1>
                <p class="text-xs text-gray-700">Courier Shipping Bill - V Clearance Document</p>
            </div>
            <div class="flex items-center gap-3 text-right text-xs">
                <div>
                    <div><strong>Invoice No:</strong> <?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></div>
                    <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
                    <div><strong>CSB Type:</strong> <?= htmlspecialchars($data['csb_type'] ?? 'CSB-V Express') ?></div>
                </div>
                <?php if (!empty($qrcodeString)): ?>
                    <div class="flex flex-col items-center">
                        <div class="irn-qr-render-box border-2 border-black p-2 bg-white shadow-md" data-qr-text="<?= htmlspecialchars($qrcodeString, ENT_QUOTES, 'UTF-8') ?>" style="width: 260px; height: 260px;"></div>
                        <span class="text-[10px] font-bold text-black mt-1.5 uppercase tracking-wider">e-Invoice GST QR Code</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

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

    <!-- Parties Grid -->
    <div class="grid grid-cols-2 gap-4 border border-black p-3 mb-4 text-xs">
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Exporter / Shipper</h3>
            <div><strong><?= htmlspecialchars($common['exporter_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['exporter_address'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['exporter_city'] ?? '') ?> - <?= htmlspecialchars($common['exporter_pincode'] ?? '') ?></div>
            <div><strong>IEC No:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '') ?></div>
            <div><strong>GSTIN:</strong> <?= htmlspecialchars($common['exporter_gstin'] ?? 'LUT Export') ?></div>
        </div>
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Consignee / Recipient</h3>
            <div><strong><?= htmlspecialchars($common['consignee_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
            <div><strong>Phone:</strong> <?= htmlspecialchars($common['consignee_phone'] ?? 'N/A') ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($common['consignee_email'] ?? 'N/A') ?></div>
        </div>
    </div>

    <!-- Shipment Header Details -->
    <div class="grid grid-cols-4 gap-2 border border-black p-2 mb-4 text-[11px] bg-gray-50">
        <div><strong>Port of Loading:</strong><br><?= htmlspecialchars($common['port_of_loading'] ?? 'INABG1') ?></div>
        <div><strong>Port of Discharge:</strong><br><?= htmlspecialchars($common['port_of_discharge'] ?? 'N/A') ?></div>
        <div><strong>Terms of Delivery:</strong><br><?= htmlspecialchars($common['terms_of_delivery'] ?? 'DDP') ?></div>
        <div><strong>Gross / Net Wt:</strong><br><?= htmlspecialchars($common['gross_weight'] ?? '0.50') ?> / <?= htmlspecialchars($common['net_weight'] ?? '0.40') ?> KG</div>
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

    <!-- Declaration & Signatures -->
    <div class="border border-black p-3 space-y-3 text-xs">
        <div>
            <strong class="uppercase block text-[10px] text-gray-700">Declaration:</strong>
            <p class="italic text-[11px] text-gray-900"><?= htmlspecialchars($data['declaration_clause'] ?? 'We declare that this invoice shows the actual price of the goods described.') ?></p>
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

<?php if (!empty($qrcodeString)): ?>
<style>
.irn-qr-render-box img {
    width: 100% !important;
    height: 100% !important;
    image-rendering: pixelated !important;
    image-rendering: crisp-edges !important;
    display: block !important;
}
.irn-qr-render-box canvas {
    display: none !important;
}
</style>
<script>
(function() {
    function renderCsb5InvoiceQr() {
        if (typeof QRCode === 'undefined') return;
        const containers = document.querySelectorAll('.irn-qr-render-box');
        containers.forEach(function(container) {
            const qrText = container.getAttribute('data-qr-text');
            if (qrText && !container.getAttribute('data-qr-done')) {
                container.setAttribute('data-qr-done', 'true');
                container.innerHTML = '';
                new QRCode(container, {
                    text: qrText,
                    width: 600,
                    height: 600,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.L
                });
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderCsb5InvoiceQr);
    } else {
        renderCsb5InvoiceQr();
    }
})();
</script>
<?php endif; ?>
