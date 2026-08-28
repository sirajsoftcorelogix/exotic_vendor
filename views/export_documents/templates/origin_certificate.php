<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
$items = $data['items'] ?? [];

$docTitle = $data['document_title'] ?? 'CERTIFICATE OF ORIGIN';
$invNo = $common['invoice_number'] ?? 'N/A';
$invDt = !empty($common['invoice_date']) ? date('d-m-Y', strtotime($common['invoice_date'])) : date('d-m-Y');
$shipperName = $common['exporter_name'] ?? 'Exotic India Art Pvt. Ltd.';
?>
<div class="export-doc-page origin-cert border border-black p-6 font-sans text-xs leading-relaxed bg-white">
    <!-- Header -->
    <div class="border-b-2 border-black pb-3 mb-4 text-center space-y-1">
        <h1 class="text-2xl font-extrabold uppercase tracking-wide text-black"><?= htmlspecialchars($docTitle) ?></h1>
        <p class="text-xs font-semibold text-gray-700 uppercase">Non-Preferential Certificate of Origin</p>
    </div>

    <!-- Shipper & Invoice Header Bar -->
    <div class="border border-black bg-gray-50 p-3 mb-4 text-xs font-semibold flex flex-wrap justify-between items-center gap-2">
        <div><span class="text-gray-600 font-normal">Shipper:</span> <strong class="text-black text-sm"><?= htmlspecialchars($shipperName) ?></strong></div>
        <div><span class="text-gray-600 font-normal">Inv No.:</span> <strong class="text-black font-mono"><?= htmlspecialchars($invNo) ?></strong></div>
        <div><span class="text-gray-600 font-normal">Inv Dt.:</span> <strong class="text-black"><?= htmlspecialchars($invDt) ?></strong></div>
    </div>

    <!-- Itemwise Information Table -->
    <div class="mb-6 overflow-x-auto">
        <h2 class="text-sm font-bold uppercase tracking-wide border-b border-black pb-1 mb-2 text-black">Itemwise Information</h2>
        
        <table class="w-full text-left border-collapse border border-black text-[10px] table-fixed">
            <thead>
                <tr class="bg-gray-100 border-b border-black font-bold text-[8.5px] text-black uppercase leading-tight">
                    <th class="border border-black p-1 text-center w-[3.5%]">Sr. No.</th>
                    <th class="border border-black p-1 w-[26.5%]">Item</th>
                    <th class="border border-black p-1 text-center w-[6.5%]">CTH</th>
                    <th class="border border-black p-1 text-center w-[6.5%]">State of Origin</th>
                    <th class="border border-black p-1 text-center w-[6.5%]">District of Origin</th>
                    <th class="border border-black p-1 text-center w-[6.5%]">SQC / Qty</th>
                    <th class="border border-black p-1 text-center w-[8%]">Preferential Agreement</th>
                    <th class="border border-black p-1 text-right w-[14%]">Taxable Amt (INR)</th>
                    <th class="border border-black p-1 text-right w-[11%]">Tax Amt (INR)</th>
                    <th class="border border-black p-1 text-right w-[11%]">Cess Amt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php $totalQty = 0; $totalTaxableInr = 0.0; $totalTaxInr = 0.0; ?>
                    <?php foreach ($items as $idx => $it): ?>
                        <?php 
                            $qty = (int)($it['quantity'] ?? 1);
                            $rate = (float)($it['unit_price'] ?? 0);
                            $amtCurr = (float)($it['amount'] ?? ($qty * $rate));
                            $exchRate = (float)($common['exchange_rate'] ?? 83.50);
                            $taxableInr = (float)($it['taxable_amt_inr'] ?? ($amtCurr * $exchRate));
                            $taxInr = (float)($it['tax_amt_inr'] ?? ($taxableInr * 0.05));
                            $cessAmt = (float)($it['cess_amt'] ?? 0.00);

                            $totalQty += $qty;
                            $totalTaxableInr += $taxableInr;
                            $totalTaxInr += $taxInr;
                        ?>
                        <tr class="border-b border-gray-300">
                            <td class="border border-black p-1 text-center font-bold break-words"><?= $idx + 1 ?></td>
                            <td class="border border-black p-1 font-medium text-gray-900 break-words">
                                <?= htmlspecialchars($it['description'] ?? 'Lady Apsara - Brass Statue') ?>
                                <?php if (!empty($it['item_code'])): ?>
                                    <div class="text-[8px] text-gray-500 font-mono"><?= htmlspecialchars($it['item_code']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="border border-black p-1 text-center font-mono text-[9px] break-words"><?= htmlspecialchars($it['hsn_code'] ?? $it['cth'] ?? '83062900') ?></td>
                            <td class="border border-black p-1 text-center uppercase font-semibold break-words"><?= htmlspecialchars($it['state_of_origin'] ?? 'DELHI') ?></td>
                            <td class="border border-black p-1 text-center uppercase break-words"><?= htmlspecialchars($it['district_of_origin'] ?? 'DELHI') ?></td>
                            <td class="border border-black p-1 text-center font-bold break-words"><?= $qty ?> NOS</td>
                            <td class="border border-black p-1 text-center text-[9px] italic text-gray-600 break-words"><?= htmlspecialchars($it['preferential_agreement'] ?? 'N/A') ?></td>
                            <td class="border border-black p-1 text-right font-mono font-bold break-words">₹<?= number_format($taxableInr, 2) ?></td>
                            <td class="border border-black p-1 text-right font-mono break-words">₹<?= number_format($taxInr, 2) ?></td>
                            <td class="border border-black p-1 text-right font-mono break-words">₹<?= number_format($cessAmt, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="font-bold bg-gray-100 border-t-2 border-black text-[9px]">
                        <td colspan="5" class="border border-black p-1 text-right uppercase">Total:</td>
                        <td class="border border-black p-1 text-center"><?= $totalQty ?> NOS</td>
                        <td class="border border-black p-1"></td>
                        <td class="border border-black p-1 text-right font-mono">₹<?= number_format($totalTaxableInr, 2) ?></td>
                        <td class="border border-black p-1 text-right font-mono">₹<?= number_format($totalTaxInr, 2) ?></td>
                        <td class="border border-black p-1 text-right font-mono">₹0.00</td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td class="border border-black p-1 text-center font-bold break-words">1</td>
                        <td class="border border-black p-1 font-medium text-gray-900 break-words">Lady Apsara - Brass Statue</td>
                        <td class="border border-black p-1 text-center font-mono text-[9px] break-words">83062900</td>
                        <td class="border border-black p-1 text-center uppercase font-semibold break-words">DELHI</td>
                        <td class="border border-black p-1 text-center uppercase break-words">DELHI</td>
                        <td class="border border-black p-1 text-center font-bold break-words">1 NOS</td>
                        <td class="border border-black p-1 text-center text-[9px] italic text-gray-600 break-words">N/A</td>
                        <td class="border border-black p-1 text-right font-mono font-bold break-words">₹32,930.25</td>
                        <td class="border border-black p-1 text-right font-mono break-words">₹1,646.51</td>
                        <td class="border border-black p-1 text-right font-mono break-words">₹0.00</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Declaration & Signatures -->
    <div class="border border-black p-4 space-y-4 text-xs mt-6 bg-gray-50/50">
        <div>
            <h3 class="font-bold uppercase text-black mb-1">Declaration by Exporter</h3>
            <p class="italic text-gray-800 text-justify">
                We hereby declare that the goods mentioned above are of Indian Origin, manufactured/produced in India, and the details specified above are true and correct.
            </p>
        </div>
        <div class="pt-6 flex justify-between items-end border-t border-gray-300">
            <div>
                <div><strong>Place:</strong> <?= htmlspecialchars($common['exporter_city'] ?? 'New Delhi') ?>, India</div>
                <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('d-m-Y')) ?></div>
            </div>
            <div class="text-right">
                <div class="font-bold uppercase mb-8">For <?= htmlspecialchars($shipperName) ?></div>
                <div class="border-t border-black pt-1 px-6 inline-block font-semibold">Authorized Signatory</div>
            </div>
        </div>
    </div>
</div>
