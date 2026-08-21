<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
$items = $data['items'] ?? [];
?>
<div class="export-doc-page declaration-textile">
    <div class="doc-header text-center border-b-2 border-black pb-3 mb-4">
        <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'HANDLOOM / TEXTILE DECLARATION') ?></h1>
        <p class="text-xs text-gray-700">Yarn Composition & Origin Declaration for Textile Export</p>
    </div>

    <div class="border border-black p-3 text-xs space-y-1 mb-4 bg-gray-50">
        <div class="flex justify-between">
            <div><strong>Exporter:</strong> <?= htmlspecialchars($common['exporter_name'] ?? '') ?></div>
            <div><strong>Invoice No:</strong> <?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></div>
        </div>
        <div class="flex justify-between">
            <div><strong>Consignee:</strong> <?= htmlspecialchars($common['consignee_name'] ?? '') ?></div>
            <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="w-full text-left text-xs border-collapse border border-black mb-4">
        <thead>
            <tr class="bg-gray-100 font-bold border-b border-black">
                <th class="border border-black p-1.5 text-center w-10">S.N.</th>
                <th class="border border-black p-1.5">Textile / Garment Item</th>
                <th class="border border-black p-1.5 w-40">Composition</th>
                <th class="border border-black p-1.5 w-28 text-center">HSN Code</th>
                <th class="border border-black p-1.5 text-center w-16">Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td class="border border-black p-1.5 text-center"><?= $idx + 1 ?></td>
                    <td class="border border-black p-1.5 font-medium"><?= htmlspecialchars($it['description'] ?? '') ?></td>
                    <td class="border border-black p-1.5"><?= htmlspecialchars($data['material_composition'] ?? '100% Pure Cotton / Silk') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= htmlspecialchars($it['hsn_code'] ?? '62149090') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= (int)($it['quantity'] ?? 1) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Declaration -->
    <div class="border border-black p-4 text-xs space-y-3 mb-4 leading-relaxed text-justify">
        <h3 class="font-bold text-black border-b border-gray-400 pb-1 uppercase">Declaration</h3>
        <p>
            <?= htmlspecialchars($data['textile_statement'] ?? 'We declare that the textile items exported herewith are handloom / handcrafted products made in India using natural fibers. All particulars are true and correct.') ?>
        </p>
    </div>

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
