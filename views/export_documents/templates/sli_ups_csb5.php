<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
?>
<div class="export-doc-page sli-ups">
    <div class="doc-header flex justify-between items-start border-b-2 border-black pb-3 mb-4">
        <div>
            <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'UPS CSB-5 SHIPPER\'S LETTER OF INSTRUCTION') ?></h1>
            <p class="text-xs text-gray-700">Courier Shipping Bill - V Clearance Instruction for UPS Express</p>
        </div>
        <div class="text-right text-xs">
            <div><strong>Carrier:</strong> <?= htmlspecialchars($data['carrier_name'] ?? 'UPS India Pvt Ltd') ?></div>
            <div><strong>Service:</strong> <?= htmlspecialchars($data['service_type'] ?? 'UPS Worldwide Express') ?></div>
            <div><strong>Account #:</strong> <?= htmlspecialchars($data['account_number'] ?? 'UPS-EXOTIC-001') ?></div>
        </div>
    </div>

    <!-- Parties -->
    <div class="grid grid-cols-2 gap-4 border border-black p-3 mb-4 text-xs">
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Shipper (Exporter)</h3>
            <div><strong><?= htmlspecialchars($common['exporter_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['exporter_address'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['exporter_city'] ?? '') ?></div>
            <div><strong>IEC:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '') ?></div>
        </div>
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Consignee</h3>
            <div><strong><?= htmlspecialchars($common['consignee_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="border border-black p-3 text-xs space-y-3 mb-4">
        <h3 class="font-bold uppercase text-gray-900 border-b border-gray-300 pb-1">Clearance & Routing Instructions</h3>
        <div><strong>Clearance Mode:</strong> <?= htmlspecialchars($data['customs_clearance_mode'] ?? 'CSB-V Express Courier Clearance') ?></div>
        <div><strong>Port of Departure:</strong> <?= htmlspecialchars($common['port_of_loading'] ?? 'INABG1') ?></div>
        <div><strong>Invoice Value:</strong> <?= htmlspecialchars($common['currency'] ?? 'USD') ?> <?= number_format((float)($common['total_amount'] ?? 0), 2) ?></div>
        <div><strong>Special Instructions:</strong> <?= htmlspecialchars($data['special_instructions'] ?? 'Express air shipment under CSB-V regulations. Handle with care.') ?></div>
    </div>

    <!-- Signatures -->
    <div class="border border-black p-3 text-xs pt-8 flex justify-between items-end">
        <div>
            <div><strong>Authorized Signatory:</strong> <?= htmlspecialchars($common['authorized_signatory'] ?? 'Authorized Signatory') ?></div>
            <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
        </div>
        <div class="text-right">
            <div class="font-bold uppercase mb-8">For <?= htmlspecialchars($common['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
            <div class="border-t border-black pt-1 px-4 inline-block font-semibold">Shipper Authorization</div>
        </div>
    </div>
</div>
