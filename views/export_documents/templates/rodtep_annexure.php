<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
?>
<div class="export-doc-page rodtep-annexure">
    <div class="doc-header text-center border-b-2 border-black pb-3 mb-4">
        <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'DECLARATION FOR RODTEP SCHEME') ?></h1>
        <p class="text-xs text-gray-700">Remission of Duties and Taxes on Exported Products (RODTEP) Scheme Annexure</p>
    </div>

    <!-- Details Box -->
    <div class="border border-black p-3 text-xs space-y-2 mb-4">
        <div class="grid grid-cols-2 gap-4">
            <div><strong>Exporter Name:</strong> <?= htmlspecialchars($common['exporter_name'] ?? '') ?></div>
            <div><strong>IEC No:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '') ?></div>
            <div><strong>Invoice No & Date:</strong> <?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?> dated <?= htmlspecialchars($common['invoice_date'] ?? date('Y-m-d')) ?></div>
            <div><strong>Port of Export:</strong> <?= htmlspecialchars($common['port_of_loading'] ?? 'INABG1') ?></div>
        </div>
    </div>

    <!-- Legal Undertaking -->
    <div class="border border-black p-4 text-xs space-y-3 mb-4 leading-relaxed text-justify">
        <h3 class="font-bold text-black border-b border-gray-400 pb-1 uppercase">Undertaking & Declaration</h3>
        <p>
            1. I/We hereby declare that I/we shall abide by the provisions, rules, notification and conditions of the Remission of Duties and Taxes on Exported Products (RODTEP) Scheme as notified by DGFT/Customs.
        </p>
        <p>
            2. I/We undertake to export the goods for which RODTEP benefit is claimed and certify that the details submitted in invoice <strong><?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></strong> are true and correct in all respects.
        </p>
        <p class="italic font-medium">
            <?= htmlspecialchars($data['rodtep_declaration'] ?? 'I/We declare that I/we shall abide by the provisions and conditions of the RODTEP scheme.') ?>
        </p>
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
