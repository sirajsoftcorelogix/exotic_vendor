<?php
/** @var array<string, mixed> $form */
/** @var array<string, mixed> $common */
$data = $form['form_data'] ?? [];
$common = $common ?? ($data['common'] ?? []);
$items = $data['items'] ?? [];

$docTitle = $data['document_title'] ?? 'CERTIFICATE OF ORIGIN';
$cooNo = 'COO-' . ($common['invoice_number'] ? preg_replace('/[^A-Za-z0-9]/', '', $common['invoice_number']) : date('Ymd-His'));
?>
<div class="export-doc-page origin-cert border border-black p-6 font-sans text-xs leading-normal bg-white">
    <!-- Top Main Title Header -->
    <div class="border-b-2 border-black pb-3 mb-3 text-center">
        <h1 class="text-xl font-extrabold uppercase tracking-wider text-black"><?= htmlspecialchars($docTitle) ?></h1>
        <p class="text-[11px] font-semibold text-gray-800 uppercase tracking-wide">Non-Preferential Export Certificate of Origin (Republic of India)</p>
        <div class="text-[10px] text-gray-600 mt-0.5">Certificate Ref No: <span class="font-mono font-bold text-black"><?= htmlspecialchars($cooNo) ?></span></div>
    </div>

    <!-- 10-Box Official Grid Structure -->
    <div class="border border-black text-xs space-y-0">
        <!-- Row 1: Exporter & Consignee -->
        <div class="grid grid-cols-2 border-b border-black divide-x divide-black">
            <div class="p-2.5">
                <span class="block font-bold text-[10px] uppercase text-gray-700 mb-1">1. Goods Consigned From (Exporter / Manufacturer)</span>
                <div class="font-bold text-sm text-black mb-0.5"><?= htmlspecialchars($common['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
                <div><?= htmlspecialchars($common['exporter_address'] ?? '101, Plaza A-1, Paschim Vihar') ?></div>
                <div><?= htmlspecialchars($common['exporter_city'] ?? 'New Delhi') ?> - <?= htmlspecialchars($common['exporter_pincode'] ?? '110063') ?>, India</div>
                <div class="mt-1"><strong>IEC No:</strong> <?= htmlspecialchars($common['exporter_iec'] ?? '0505012345') ?> | <strong>GSTIN:</strong> <?= htmlspecialchars($common['exporter_gstin'] ?? '07AADCE1400C1ZJ') ?></div>
            </div>
            <div class="p-2.5">
                <span class="block font-bold text-[10px] uppercase text-gray-700 mb-1">2. Goods Consigned To (Consignee / Importer)</span>
                <div class="font-bold text-sm text-black mb-0.5"><?= htmlspecialchars($common['consignee_name'] ?? 'N/A') ?></div>
                <div><?= htmlspecialchars($common['consignee_address_line1'] ?? '') ?></div>
                <?php if (!empty($common['consignee_address_line2'])): ?>
                    <div><?= htmlspecialchars($common['consignee_address_line2']) ?></div>
                <?php endif; ?>
                <div><?= htmlspecialchars($common['consignee_city'] ?? '') ?>, <?= htmlspecialchars($common['consignee_state'] ?? '') ?> <?= htmlspecialchars($common['consignee_zipcode'] ?? '') ?></div>
                <div class="font-bold text-black uppercase mt-0.5">Country: <?= htmlspecialchars($common['consignee_country'] ?? 'N/A') ?></div>
            </div>
        </div>

        <!-- Row 2: Transport & Country details -->
        <div class="grid grid-cols-3 border-b border-black divide-x divide-black bg-gray-50 text-[11px]">
            <div class="p-2">
                <span class="block font-bold text-[10px] uppercase text-gray-700 mb-0.5">3. Means of Transport & Route</span>
                <div><strong>Carrier / Mode:</strong> <?= htmlspecialchars($common['terms_of_delivery'] ?? 'Express Air Freight') ?></div>
                <div><strong>Port of Loading:</strong> <?= htmlspecialchars($common['port_of_loading'] ?? 'INABG1 (New Delhi)') ?></div>
                <div><strong>Port of Discharge:</strong> <?= htmlspecialchars($common['port_of_discharge'] ?? $common['consignee_city'] ?? 'Destination') ?></div>
            </div>
            <div class="p-2">
                <span class="block font-bold text-[10px] uppercase text-gray-700 mb-0.5">4. Country of Origin</span>
                <div class="text-sm font-extrabold uppercase text-black">REPUBLIC OF INDIA</div>
                <div class="text-[10px] text-gray-600 mt-1">Origin Criterion: <strong class="text-black"><?= htmlspecialchars($data['origin_criterion'] ?? 'Wholly Produced (P)') ?></strong></div>
            </div>
            <div class="p-2">
                <span class="block font-bold text-[10px] uppercase text-gray-700 mb-0.5">5. Country of Destination</span>
                <div class="text-sm font-extrabold uppercase text-black"><?= htmlspecialchars($common['consignee_country'] ?? 'N/A') ?></div>
                <div class="text-[10px] text-gray-600 mt-1">Final Dest: <strong class="text-black"><?= htmlspecialchars($common['final_destination'] ?? $common['consignee_city'] ?? '') ?></strong></div>
            </div>
        </div>

        <!-- Row 3: Invoice References -->
        <div class="grid grid-cols-4 border-b border-black divide-x divide-black p-2 bg-gray-100 text-[11px]">
            <div><strong>Invoice No:</strong><br><span class="font-bold font-mono text-black"><?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></span></div>
            <div><strong>Invoice Date:</strong><br><?= htmlspecialchars($common['invoice_date'] ?? date('Y-m-d')) ?></div>
            <div><strong>Order Ref:</strong><br><?= htmlspecialchars($common['order_number'] ?? 'N/A') ?></div>
            <div><strong>Invoice Value:</strong><br><strong class="text-black"><?= htmlspecialchars($common['currency'] ?? 'USD') ?> <?= number_format((float)($common['total_amount'] ?? 0), 2) ?></strong></div>
        </div>

        <!-- Row 4: Itemwise Information Table (Customs DGFT COO Format) -->
        <div class="p-0 border-b border-black">
            <div class="bg-gray-100 p-2 border-b border-black font-bold uppercase text-[10px] flex justify-between items-center">
                <span>6. Itemwise Information (Customs CTH & Origin Declaration)</span>
                <span class="text-[9px] font-normal text-gray-700">Shipper: <strong><?= htmlspecialchars($common['exporter_name'] ?? 'Exotic India Art Pvt. Ltd.') ?></strong> | Inv No: <strong><?= htmlspecialchars($common['invoice_number'] ?? 'N/A') ?></strong> | Inv Dt: <strong><?= htmlspecialchars($common['invoice_date'] ? date('d-m-Y', strtotime($common['invoice_date'])) : date('d-m-Y')) ?></strong></span>
            </div>
            <table class="w-full text-left border-collapse text-[10px]">
                <thead>
                    <tr class="bg-gray-200 border-b border-black font-bold uppercase text-[9px]">
                        <th class="p-1.5 border-r border-black text-center w-8">Sr. No.</th>
                        <th class="p-1.5 border-r border-black">Item Description</th>
                        <th class="p-1.5 border-r border-black text-center w-16">CTH / HSN</th>
                        <th class="p-1.5 border-r border-black text-center w-14">State of Origin</th>
                        <th class="p-1.5 border-r border-black text-center w-16">District of Origin</th>
                        <th class="p-1.5 border-r border-black text-center w-14">SQC / Qty</th>
                        <th class="p-1.5 border-r border-black text-center w-24">Preferential Agreement</th>
                        <th class="p-1.5 border-r border-black text-right w-20">Taxable Amt (INR)</th>
                        <th class="p-1.5 border-r border-black text-right w-16">Tax Amt (INR)</th>
                        <th class="p-1.5 text-right w-14">Cess Amt</th>
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
                                $taxInr = (float)($it['tax_amt_inr'] ?? ($taxableInr * 0.05)); // Default 5% IGST or calculate
                                $cessAmt = (float)($it['cess_amt'] ?? 0.00);

                                $totalQty += $qty;
                                $totalTaxableInr += $taxableInr;
                                $totalTaxInr += $taxInr;
                            ?>
                            <tr class="<?= $idx % 2 === 1 ? 'bg-gray-50' : '' ?>">
                                <td class="p-1.5 border-r border-b border-gray-300 text-center font-bold"><?= $idx + 1 ?></td>
                                <td class="p-1.5 border-r border-b border-gray-300 font-medium text-gray-900">
                                    <?= htmlspecialchars($it['description'] ?? 'Lady Apsara - Brass Statue') ?>
                                    <div class="text-[8px] text-gray-500 font-mono"><?= htmlspecialchars($it['item_code'] ?? '') ?></div>
                                </td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-center font-mono text-[9px]"><?= htmlspecialchars($it['hsn_code'] ?? $it['cth'] ?? '83062900') ?></td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-center font-bold uppercase"><?= htmlspecialchars($it['state_of_origin'] ?? $common['exporter_city'] ?? 'DELHI') ?></td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-center uppercase"><?= htmlspecialchars($it['district_of_origin'] ?? $common['exporter_city'] ?? 'DELHI') ?></td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-center font-bold"><?= $qty ?> NOS</td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-center text-[8px] italic text-gray-600"><?= htmlspecialchars($it['preferential_agreement'] ?? 'N/A (Non-Preferential)') ?></td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-right font-mono font-semibold">₹<?= number_format($taxableInr, 2) ?></td>
                                <td class="p-1.5 border-r border-b border-gray-300 text-right font-mono text-gray-700">₹<?= number_format($taxInr, 2) ?></td>
                                <td class="p-1.5 border-b border-gray-300 text-right font-mono text-gray-500">₹<?= number_format($cessAmt, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="font-bold bg-gray-100 border-t-2 border-black">
                            <td colspan="5" class="p-1.5 border-r border-black text-right uppercase text-[9px]">Total Itemwise Summary:</td>
                            <td class="p-1.5 border-r border-black text-center text-xs"><?= $totalQty ?> NOS</td>
                            <td class="p-1.5 border-r border-black"></td>
                            <td class="p-1.5 border-r border-black text-right font-mono text-xs text-black">₹<?= number_format($totalTaxableInr, 2) ?></td>
                            <td class="p-1.5 border-r border-black text-right font-mono text-xs text-gray-800">₹<?= number_format($totalTaxInr, 2) ?></td>
                            <td class="p-1.5 text-right font-mono text-xs text-gray-600">₹0.00</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td class="p-1.5 border-r border-b border-gray-300 text-center font-bold">1</td>
                            <td class="p-1.5 border-r border-b border-gray-300 font-medium text-gray-900">Lady Apsara - Brass Statue</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-center font-mono text-[9px]">83062900</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-center font-bold uppercase">DELHI</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-center uppercase">DELHI</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-center font-bold">1 NOS</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-center text-[8px] italic text-gray-600">N/A (Non-Preferential)</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-right font-mono font-semibold">₹32,930.25</td>
                            <td class="p-1.5 border-r border-b border-gray-300 text-right font-mono text-gray-700">₹1,646.51</td>
                            <td class="p-1.5 border-b border-gray-300 text-right font-mono text-gray-500">₹0.00</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Row 5: Declarations & Certifications -->
        <div class="grid grid-cols-2 divide-x divide-black text-[11px]">
            <!-- Exporter Declaration (Box 9) -->
            <div class="p-3 space-y-3 flex flex-col justify-between">
                <div>
                    <span class="block font-bold text-[10px] uppercase text-gray-700 mb-1">8. Declaration by the Exporter</span>
                    <p class="text-[10px] leading-relaxed text-gray-800 text-justify">
                        The undersigned hereby declares that the above details and statements are true and correct; that all the goods were produced / manufactured in <strong>INDIA</strong> and that they comply with the origin requirements specified for non-preferential trade.
                    </p>
                </div>
                <div class="pt-6 border-t border-gray-300">
                    <div class="text-[10px] text-gray-600 mb-6">
                        <div><strong>Place:</strong> <?= htmlspecialchars($common['exporter_city'] ?? 'New Delhi') ?>, India</div>
                        <div><strong>Date:</strong> <?= htmlspecialchars($data['declaration_date'] ?? date('Y-m-d')) ?></div>
                    </div>
                    <div class="font-bold uppercase text-[10px]">For <?= htmlspecialchars($common['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
                    <div class="border-t border-black pt-1 inline-block font-bold text-[10px] mt-6">Authorized Signatory</div>
                </div>
            </div>

            <!-- Certification / Chamber Approval (Box 10) -->
            <div class="p-3 space-y-3 flex flex-col justify-between bg-gray-50/50">
                <div>
                    <span class="block font-bold text-[10px] uppercase text-gray-700 mb-1">9. Certification / Issuing Authority</span>
                    <p class="text-[10px] text-gray-700 leading-relaxed">
                        It is hereby certified, on the basis of control carried out / declaration submitted by the exporter, that the goods described above originate in <strong>INDIA</strong>.
                    </p>
                    <div class="mt-2 text-[10px] font-semibold text-black">
                        Issuing Body: <?= htmlspecialchars($data['issuing_authority'] ?? 'Federation of Indian Export Organisations (FIEO) / Chamber of Commerce') ?>
                    </div>
                </div>
                <div class="pt-6 border-t border-gray-300 text-right">
                    <div class="h-12 border border-dashed border-gray-400 rounded p-1 mb-2 inline-block w-28 text-center align-middle text-[8px] text-gray-400">
                        [ Official Chamber / Seal Stamp ]
                    </div>
                    <div class="border-t border-black pt-1 inline-block font-bold text-[10px]">Authorized Officer Signature & Stamp</div>
                </div>
            </div>
        </div>
    </div>
</div>

