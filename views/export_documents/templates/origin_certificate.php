<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
?>
<div class="export-doc-page origin-cert">
    <div class="doc-header text-center border-b-2 border-black pb-3 mb-4">
        <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'CERTIFICATE OF ORIGIN') ?></h1>
        <p class="text-xs text-gray-700">Non-Preferential Export Certificate of Origin</p>
    </div>

    <!-- Parties Grid -->
    <div class="grid grid-cols-2 gap-4 border border-black p-3 mb-4 text-xs">
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">1. Shipper / Exporter</h3>
            <div><strong><?= htmlspecialchars($common['exporter_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['exporter_address'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['exporter_city'] ?? '') ?>, India</div>
        </div>
        <div>
            <h3 class="font-bold text-black border-b border-gray-400 pb-1 mb-1 uppercase">2. Consignee</h3>
            <div><strong><?= htmlspecialchars($common['consignee_name'] ?? '') ?></strong></div>
            <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
            <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
        </div>
    </div>

    <!-- Transport details -->
    <div class="border border-black p-3 text-xs space-y-2 mb-4">
        <h3 class="font-bold text-black border-b border-gray-400 pb-1 uppercase">3. Transport & Route Details</h3>
        <div class="grid grid-cols-3 gap-2">
            <div><strong>Means of Transport:</strong> Air Freight</div>
            <div><strong>Country of Origin:</strong> Republic of India</div>
            <div><strong>Country of Destination:</strong> <?= htmlspecialchars($common['consignee_country'] ?? '') ?></div>
        </div>
        <div><strong>Invoice Reference:</strong> Invoice No. <?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?> dated <?= htmlspecialchars($common['invoice_date'] ?? date('Y-m-d')) ?></div>
    </div>

    <!-- Certification Clause -->
    <div class="border border-black p-4 text-xs space-y-4 mb-4">
        <h3 class="font-bold text-black border-b border-gray-400 pb-1 uppercase">4. Declaration by Exporter</h3>
        <p class="leading-relaxed text-justify">
            The undersigned hereby declares that the above-mentioned goods were produced / manufactured in <strong>INDIA</strong> and that they comply with the origin requirements specified for non-preferential trade.
        </p>
        <div><strong>Origin Criterion:</strong> <?= htmlspecialchars($data['origin_criterion'] ?? 'Wholly Produced in India (P)') ?></div>
    </div>

    <!-- Signatures -->
    <div class="border border-black p-3 text-xs pt-8 flex justify-between items-end">
        <div>
            <div><strong>Issuing Authority:</strong> <?= htmlspecialchars($data['issuing_authority'] ?? 'Self Declaration / Chamber of Commerce') ?></div>
            <div><strong>Date & Place:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?>, <?= htmlspecialchars($common['exporter_city'] ?? 'New Delhi') ?></div>
        </div>
        <div class="text-right">
            <div class="font-bold uppercase mb-8">For <?= htmlspecialchars($common['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
            <div class="border-t border-black pt-1 px-4 inline-block font-semibold">Authorized Signatory</div>
        </div>
    </div>
</div>
