<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
?>
<div class="export-doc-page sli-commercial">
    <div class="doc-header flex justify-between items-start border-b-2 border-black pb-3 mb-4">
        <div>
            <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'SHIPPER\'S LETTER OF INSTRUCTION (SLI)') ?></h1>
            <p class="text-xs text-gray-700">Commercial Cargo Clearance Instruction for DHL / FedEx / UPS</p>
        </div>
        <div class="text-right text-xs">
            <div><strong>Drawback Status:</strong> <?= htmlspecialchars($data['drawback_status'] ?? 'Drawback Shipment') ?></div>
            <div><strong>Export Scheme:</strong> <?= htmlspecialchars($data['export_scheme'] ?? 'RODTEP / Drawback') ?></div>
            <div><strong>Port Code:</strong> <?= htmlspecialchars($data['port_code'] ?? $common['port_of_loading'] ?? 'INABG1') ?></div>
        </div>
    </div>

    <!-- Parties Grid -->
    <div class="grid grid-cols-2 gap-4 border border-black p-3 mb-4 text-xs">
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Shipper (Exporter)</h3>
            <div><strong><?= htmlspecialchars($common['exporter_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['exporter_address'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['exporter_city'] ?? '') ?></div>
            <div><strong>IEC No:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '') ?></div>
            <div><strong>GSTIN:</strong> <?= htmlspecialchars($common['exporter_gstin'] ?? '') ?></div>
        </div>
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">Consignee</h3>
            <div><strong><?= htmlspecialchars($common['consignee_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
            <div><strong>Destination Port:</strong> <?= htmlspecialchars($common['port_of_discharge'] ?? '') ?></div>
        </div>
    </div>

    <!-- Authorization Body -->
    <div class="border border-black p-3 text-xs space-y-3 mb-4">
        <p class="leading-relaxed text-justify">
            We hereby authorize <strong><?= htmlspecialchars($data['cha_name'] ?? 'Authorized Freight Forwarder / Express Courier CHA Agent') ?></strong> to act as our Custom House Agent to file Shipping Bill and complete all export customs clearance formalities for the invoice <strong><?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></strong> under the rules of Indian Customs.
        </p>
        <div class="grid grid-cols-3 gap-2 bg-gray-50 p-2 border border-gray-300 font-medium">
            <div>Invoice Value: <strong><?= htmlspecialchars($common['currency'] ?? 'USD') ?> <?= number_format((float)($common['total_amount'] ?? 0), 2) ?></strong></div>
            <div>Gross Weight: <strong><?= htmlspecialchars($common['gross_weight'] ?? '0.50') ?> KG</strong></div>
            <div>Packages: <strong><?= htmlspecialchars($common['total_packages'] ?? 1) ?> Box(es)</strong></div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="border border-black p-3 text-xs pt-8 flex justify-between items-end">
        <div>
            <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
            <div><strong>Place:</strong> <?= htmlspecialchars($common['exporter_city'] ?? 'New Delhi') ?></div>
        </div>
        <div class="text-right">
            <div class="font-bold uppercase mb-8">For <?= htmlspecialchars($common['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
            <div class="border-t border-black pt-1 px-4 inline-block font-semibold">Authorized Signatory</div>
        </div>
    </div>
</div>
