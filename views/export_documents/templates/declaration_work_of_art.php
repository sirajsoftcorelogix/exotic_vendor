<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
$items = $data['items'] ?? [];
?>
<div class="export-doc-page declaration-art">
    <div class="doc-header text-center border-b-2 border-black pb-3 mb-4">
        <h1 class="text-xl font-bold uppercase tracking-wider text-black"><?= htmlspecialchars($data['document_title'] ?? 'WORK OF ART DECLARATION') ?></h1>
        <p class="text-xs text-gray-700">Non-Antiquity Certification under the Antiquities and Art Treasures Act, 1972</p>
    </div>

    <!-- Reference Box -->
    <div class="border border-black p-3 text-xs space-y-1 mb-4 bg-gray-50">
        <div class="flex justify-between">
            <div><strong>Exporter:</strong> <?= htmlspecialchars($common['exporter_name'] ?? '') ?></div>
            <div><strong>Invoice No:</strong> <?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></div>
        </div>
        <div class="flex justify-between">
            <div><strong>Consignee:</strong> <?= htmlspecialchars($common['consignee_name'] ?? '') ?> (<?= htmlspecialchars($common['consignee_country'] ?? '') ?>)</div>
            <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
        </div>
    </div>

    <!-- Art Items Table -->
    <table class="w-full text-left text-xs border-collapse border border-black mb-4">
        <thead>
            <tr class="bg-gray-100 font-bold border-b border-black">
                <th class="border border-black p-1.5 text-center w-10">S.N.</th>
                <th class="border border-black p-1.5">Description of Artwork</th>
                <th class="border border-black p-1.5 w-36">Type / Medium</th>
                <th class="border border-black p-1.5 w-32">Age / Creation Year</th>
                <th class="border border-black p-1.5 text-center w-16">Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td class="border border-black p-1.5 text-center"><?= $idx + 1 ?></td>
                    <td class="border border-black p-1.5 font-medium"><?= htmlspecialchars($it['description'] ?? '') ?></td>
                    <td class="border border-black p-1.5"><?= htmlspecialchars($data['art_type'] ?? 'Brass Sculpture / Handcrafted Painting') ?></td>
                    <td class="border border-black p-1.5"><?= htmlspecialchars($data['year_of_creation'] ?? 'Modern (Under 100 Years)') ?></td>
                    <td class="border border-black p-1.5 text-center"><?= (int)($it['quantity'] ?? 1) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Non Antiquity Declaration -->
    <div class="border border-black p-4 text-xs space-y-3 mb-4 leading-relaxed text-justify">
        <h3 class="font-bold text-black border-b border-gray-400 pb-1 uppercase">Declaration & Guarantee</h3>
        <p>
            <?= htmlspecialchars($data['antiquity_declaration'] ?? 'We hereby declare and guarantee that the art items listed above are modern handicrafts/artistic creations produced within the last 100 years. They do NOT fall under the Antiquities and Art Treasures Act, 1972, and do not require an Antiquity Export License.') ?>
        </p>
        <p>
            We further certify that the artwork was created by modern Indian artisans/artists using non-prohibited materials.
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
