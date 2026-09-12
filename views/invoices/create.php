<?php
require_once __DIR__ . '/../../helpers/invoice/invoice_address_html.php';
$is_international = false;
$invoiceCurrency = $data[0]['currency'] ?? 'INR';
if (!empty($invoiceCurrency) && $invoiceCurrency !== 'INR') {
    $is_international = true;
}
$invoiceItemCount = (isset($data) && is_array($data)) ? count($data) : 0;
$invoiceOrderNumbers = [];
if (isset($data) && is_array($data)) {
    foreach ($data as $row) {
        $orderNo = trim((string) ($row['order_number'] ?? ''));
        if ($orderNo !== '' && !in_array($orderNo, $invoiceOrderNumbers, true)) {
            $invoiceOrderNumbers[] = $orderNo;
        }
    }
}
$invInputClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200';
$invLabelClass = 'block text-xs font-semibold uppercase tracking-wide text-gray-500';
?>
<style>
    .invoice-create-page .inv-input { height: auto; min-height: 2.5rem; line-height: 1.4; }
    .invoice-create-page .inv-action-btn { width: auto; height: auto; }
    .invoice-create-page .inv-table th,
    .invoice-create-page .inv-table td { vertical-align: middle; }
</style>
<div class="invoice-create-page mx-auto max-w-[1400px] space-y-5 px-3 py-5 md:px-6">
    <form action="<?php echo base_url('?page=invoices&action=create_post'); ?>" id="create_invoice" method="post" class="space-y-5">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:flex-row md:items-center md:justify-between md:p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-500 text-white shadow-[0px_10px_15px_-3px_#0000001A]">
                    <i class="fas fa-file-invoice text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Create Invoice</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Review addresses, GST, and line items before generating the invoice.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <?php if ($is_international): ?>
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800">Export / IRN</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">Domestic</span>
                        <?php endif; ?>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700"><?php echo htmlspecialchars((string) $invoiceCurrency, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-800"><?php echo (int) $invoiceItemCount; ?> item<?php echo $invoiceItemCount === 1 ? '' : 's'; ?></span>
                        <?php foreach ($invoiceOrderNumbers as $orderNo): ?>
                            <a href="<?php echo htmlspecialchars(base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . rawurlencode($orderNo)), ENT_QUOTES, 'UTF-8'); ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-xs font-medium text-orange-800 ring-1 ring-orange-200 hover:bg-orange-50 hover:underline"
                               title="View order details">Order <?php echo htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Invoice date</div>
                <div class="mt-1 text-base font-semibold text-slate-800"><?php echo date('d M Y'); ?></div>
                <input type="hidden" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <!--international section add fields  -->
        <?php
        if ($is_international) {
            $intl = $international_defaults ?? [];
            $intlVal = static function (string $key) use ($intl): string {
                if (!isset($intl[$key])) {
                    return '';
                }
                $value = $intl[$key];
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            };
        ?>
            <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:p-6" id="internationalSection">
                <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Export / IRN details</h2>
                        <p class="text-sm text-gray-500">Pre-filled from the order and currency master. Review before creating the invoice.</p>
                    </div>
                </div>
                <div class="mb-5">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Shipment route</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <?php
                $shippingPorts = is_array($shipping_ports ?? null) ? $shipping_ports : [];
                $shippingPortTypes = is_array($shipping_port_types ?? null) ? $shipping_port_types : [];
                $selectedPreCarriage = (string) ($intl['pre_carriage_by'] ?? 'Air');
                $selectedLoading = (string) ($intl['port_of_loading'] ?? '');
                $selectedShippingPort = strtoupper((string) ($intl['shipping_port'] ?? ''));
                $preCarriageValues = class_exists('PortMaster') ? PortMaster::preCarriageValues() : [];
                ?>
                <div>
                    <label for="pre_carriage_by" class="<?php echo $invLabelClass; ?>">Pre Carriage By</label>
                    <?php if ($shippingPortTypes !== []): ?>
                        <select name="pre_carriage_by" id="pre_carriage_by" class="<?php echo $invInputClass; ?> inv-input">
                            <?php foreach ($shippingPortTypes as $typeKey => $typeLabel): ?>
                                <?php
                                $preValue = $preCarriageValues[$typeKey] ?? $typeLabel;
                                $preSelected = strcasecmp($selectedPreCarriage, (string) $preValue) === 0
                                    || strcasecmp($selectedPreCarriage, (string) $typeLabel) === 0
                                    || strcasecmp($selectedPreCarriage, (string) $typeKey) === 0;
                                ?>
                                <option value="<?php echo htmlspecialchars((string) $preValue, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-port-type="<?php echo htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $preSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="pre_carriage_by" id="pre_carriage_by" value="<?php echo $intlVal('pre_carriage_by'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                    <?php endif; ?>
                </div>
                <div>
                    <label for="port_of_loading" class="<?php echo $invLabelClass; ?>">Port of Loading</label>
                    <?php if ($shippingPorts !== []): ?>
                        <select name="port_of_loading" id="port_of_loading" class="<?php echo $invInputClass; ?> inv-input">
                            <option value="">Select port of loading</option>
                            <?php foreach ($shippingPorts as $portRow): ?>
                                <?php
                                $portOption = PortMaster::formatPortOption($portRow);
                                $portCode = strtoupper(trim((string) ($portRow['port_code'] ?? '')));
                                $portType = (string) ($portRow['port_type'] ?? '');
                                $isSelected = $portOption === $selectedLoading
                                    || ($portCode !== '' && $portCode === $selectedShippingPort);
                                ?>
                                <option value="<?php echo htmlspecialchars($portOption, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-port-code="<?php echo htmlspecialchars($portCode, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-port-type="<?php echo htmlspecialchars($portType, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $isSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($portOption, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="port_of_loading" id="port_of_loading" value="<?php echo $intlVal('port_of_loading'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                    <?php endif; ?>
                </div>
                <div>
                    <label for="port_of_discharge" class="<?php echo $invLabelClass; ?>">Port of Discharge</label>
                    <input type="text" name="port_of_discharge" id="port_of_discharge" value="<?php echo $intlVal('port_of_discharge'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="country_of_origin" class="<?php echo $invLabelClass; ?>">Country of Origin</label>
                    <input type="text" name="country_of_origin" id="country_of_origin" value="India" readonly class="<?php echo $invInputClass; ?> inv-input bg-gray-100 text-gray-700">
                </div>
                <div>
                    <label for="country_of_final_destination" class="<?php echo $invLabelClass; ?>">Country of Final Destination</label>
                    <?php
                    $invoiceCountries = is_array($invoice_countries ?? null) ? $invoice_countries : [];
                    $selectedDestCountry = (string) ($intl['country_of_final_destination'] ?? '');
                    $selectedDestCode = strtoupper((string) ($intl['shipping_country_code'] ?? ''));
                    ?>
                    <?php if ($invoiceCountries !== []): ?>
                        <select name="country_of_final_destination" id="country_of_final_destination" class="<?php echo $invInputClass; ?> inv-input">
                            <option value="">Select country</option>
                            <?php foreach ($invoiceCountries as $countryRow): ?>
                                <?php
                                $countryName = trim((string) ($countryRow['name'] ?? ''));
                                $countryCode = strtoupper(trim((string) ($countryRow['country_code'] ?? '')));
                                if ($countryName === '') {
                                    continue;
                                }
                                $countrySelected = strcasecmp($selectedDestCountry, $countryName) === 0
                                    || ($countryCode !== '' && $countryCode === $selectedDestCode)
                                    || ($countryCode !== '' && strcasecmp($selectedDestCountry, $countryCode) === 0);
                                $countryLabel = $countryCode !== '' ? $countryName . ' (' . $countryCode . ')' : $countryName;
                                ?>
                                <option value="<?php echo htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-country-code="<?php echo htmlspecialchars($countryCode, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $countrySelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($countryLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="country_of_final_destination" id="country_of_final_destination" value="<?php echo $intlVal('country_of_final_destination'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                    <?php endif; ?>
                </div>
                <div>
                    <label for="final_destination" class="<?php echo $invLabelClass; ?>">Final Destination</label>
                    <input type="text" name="final_destination" id="final_destination" value="<?php echo $intlVal('final_destination'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                    </div>
                </div>
                <div class="mb-5 border-t border-gray-100 pt-5">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Charges</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="usd_export_rate" class="<?php echo $invLabelClass; ?>">USD Export Rate</label>
                    <input type="number" name="usd_export_rate" id="usd_export_rate" step="0.01" value="<?php echo $intlVal('usd_export_rate'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="ap_cost" class="<?php echo $invLabelClass; ?>">AP Cost</label>
                    <input type="number" name="ap_cost" id="ap_cost" step="0.01" value="<?php echo $intlVal('ap_cost'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="freight_charge" class="<?php echo $invLabelClass; ?>">Freight Charge</label>
                    <input type="number" name="freight_charge" id="freight_charge" step="0.01" value="<?php echo $intlVal('freight_charge'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="insurance_charge" class="<?php echo $invLabelClass; ?>">Insurance Charge</label>
                    <input type="number" name="insurance_charge" id="insurance_charge" step="0.01" value="<?php echo $intlVal('insurance_charge'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                    </div>
                </div>
                <div>
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Shipping bill</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="shipping_bill_number" class="<?php echo $invLabelClass; ?>">Shipping Bill Number</label>
                    <input type="text" name="shipping_bill_number" id="shipping_bill_number" value="<?php echo $intlVal('shipping_bill_number'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="shipping_bill_date" class="<?php echo $invLabelClass; ?>">Shipping Bill Date</label>
                    <input type="date" name="shipping_bill_date" id="shipping_bill_date" value="<?php echo $intlVal('shipping_bill_date'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="shipping_port" class="<?php echo $invLabelClass; ?>">Shipping Port Code</label>
                    <input type="text" name="shipping_port" id="shipping_port" value="<?php echo $intlVal('shipping_port'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="shipping_ref_clm" class="<?php echo $invLabelClass; ?>">Shipping Ref CLM</label>
                    <input type="text" name="shipping_ref_clm" id="shipping_ref_clm" value="<?php echo $intlVal('shipping_ref_clm'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="shipping_currency" class="<?php echo $invLabelClass; ?>">Shipping Currency</label>
                    <input type="text" name="shipping_currency" id="shipping_currency" value="<?php echo $intlVal('shipping_currency'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="shipping_country_code" class="<?php echo $invLabelClass; ?>">Shipping Country Code</label>
                    <input type="text" name="shipping_country_code" id="shipping_country_code" value="<?php echo $intlVal('shipping_country_code'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="shipping_exp_duty" class="<?php echo $invLabelClass; ?>">Shipping Exp Duty</label>
                    <input type="number" name="shipping_exp_duty" id="shipping_exp_duty" step="0.01" value="<?php echo $intlVal('shipping_exp_duty'); ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                    </div>
                </div>
            </div>
        <!-- Transporter & Vehicle Information -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:p-6" id="transportSelectionSection">
            <h2 class="mb-1 text-base font-semibold text-slate-800">Transporter &amp; vehicle</h2>
            <p class="mb-4 text-sm text-gray-500">Choose a registered transporter ID or transport mode for e-way bill details.</p>
            <div class="mb-4 inline-flex rounded-lg bg-gray-100 p-1 text-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-md px-3 py-1.5 has-[:checked]:bg-white has-[:checked]:font-semibold has-[:checked]:shadow-sm">
                    <input type="radio" name="transport_selection" value="id" checked class="h-4 w-4 text-orange-600" data-transport-selection>
                    <span>Transport ID</span>
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-md px-3 py-1.5 has-[:checked]:bg-white has-[:checked]:font-semibold has-[:checked]:shadow-sm">
                    <input type="radio" name="transport_selection" value="mode" class="h-4 w-4 text-orange-600" data-transport-selection>
                    <span>Transport Mode</span>
                </label>
            </div>
            <div id="transportModeFields" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="invoice_trans_mode" class="<?php echo $invLabelClass; ?>">Transport Mode</label>
                    <select name="trans_mode" id="invoice_trans_mode" class="<?php echo $invInputClass; ?> inv-input">
                        <option value="1">1 - Road</option>
                        <option value="2">2 - Rail</option>
                        <option value="3">3 - Air</option>
                        <option value="4">4 - Ship</option>
                    </select>
                </div>
                <div>
                    <label for="invoice_veh_no" class="<?php echo $invLabelClass; ?>">Vehicle Number</label>
                    <input type="text" name="veh_no" id="invoice_veh_no" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="invoice_veh_type" class="<?php echo $invLabelClass; ?>">Vehicle Type</label>
                    <select name="veh_type" id="invoice_veh_type" class="<?php echo $invInputClass; ?> inv-input">
                        <option value="R">R - Regular</option>
                        <option value="ODC">ODC - Over Dimensional Cargo</option>
                    </select>
                </div>
                <div>
                    <label for="invoice_trans_doc_no" class="<?php echo $invLabelClass; ?>">Transport Document No.</label>
                    <input type="text" name="trans_doc_no" id="invoice_trans_doc_no" class="<?php echo $invInputClass; ?> inv-input">
                </div>
                <div>
                    <label for="invoice_trans_doc_dt" class="<?php echo $invLabelClass; ?>">Transport Document Date</label>
                    <input type="text" name="trans_doc_dt" id="invoice_trans_doc_dt" value="<?= date('d/m/Y') ?>" class="<?php echo $invInputClass; ?> inv-input">
                </div>
            </div>
            <div id="transportIdFields" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="invoice_transporter_id" class="<?php echo $invLabelClass; ?>">Transport ID</label>
                    <select name="trans_id" id="invoice_transporter_id" class="<?php echo $invInputClass; ?> inv-input">
                        <option value="">-- Select transporter --</option>
                        <?php foreach (($eway_transporters ?? []) as $ewayTransporter): ?>
                            <?php
                            $transporterName = trim((string)($ewayTransporter['trans_name'] ?? ''));
                            $transporterGstin = strtoupper(trim((string)($ewayTransporter['gstin'] ?? '')));
                            if ($transporterName === '' || $transporterGstin === '') continue;
                            ?>
                            <option value="<?= htmlspecialchars($transporterGstin, ENT_QUOTES, 'UTF-8') ?>" data-transporter-name="<?= htmlspecialchars($transporterName, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($transporterName, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($transporterGstin, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="invoice_transporter_name" class="<?php echo $invLabelClass; ?>">Transporter Name</label>
                    <input type="text" name="trans_name" id="invoice_transporter_name" readonly class="<?php echo $invInputClass; ?> inv-input bg-gray-100">
                </div>
            </div>
        </div>

        <?php } ?>
        <?php
                // Helper function to format single-line address
                if (!function_exists('formatAddress')) {
                function formatAddress($addr)
                {
                    $parts = [];
                    if (!empty($addr['first_name'])) $parts[] = $addr['first_name'] . ' ' . $addr['last_name'];
                    if (!empty($addr['address_line1'])) $parts[] = $addr['address_line1'];
                    if (!empty($addr['address_line2'])) $parts[] = $addr['address_line2'];
                    if (!empty($addr['city'])) $parts[] = $addr['city'];
                    if (!empty($addr['state'])) $parts[] = $addr['state'];
                    if (!empty($addr['zipcode'])) $parts[] = $addr['zipcode'];
                    if (!empty($addr['country'])) $parts[] = $addr['country'];
                    return implode(', ', $parts);
                }
                }

                // Build Bill To addresses list (unique)
                $billToAddresses = [];
                $shipToAddresses = [];
                $billingState = $customer_address[0]['state'] ?? '';
                $firmState = $firm['state'] ?? '';

                // Store firm state for JavaScript
                $firmStateJS = json_encode($firmState);
                if (isset($customer_address) && is_array($customer_address) && count($customer_address) > 0) {
                    foreach ($customer_address as $addr) {
                        // Build billing address
                        $billAddr = formatAddress($addr);
                        if (!empty($billAddr) && !in_array($billAddr, $billToAddresses)) {
                            $billToAddresses[] = $billAddr;
                        }

                        // Build shipping address if exists
                        if (!empty($addr['shipping_address_line1'])) {
                            $shipParts = [];
                            if (!empty($addr['shipping_first_name'])) $shipParts[] = $addr['shipping_first_name'] . ' ' . $addr['shipping_last_name'];
                            if (!empty($addr['shipping_address_line1'])) $shipParts[] = $addr['shipping_address_line1'];
                            if (!empty($addr['shipping_address_line2'])) $shipParts[] = $addr['shipping_address_line2'];
                            if (!empty($addr['shipping_city'])) $shipParts[] = $addr['shipping_city'];
                            if (!empty($addr['shipping_state'])) $shipParts[] = $addr['shipping_state'];
                            if (!empty($addr['shipping_zipcode'])) $shipParts[] = $addr['shipping_zipcode'];
                            if (!empty($addr['shipping_country'])) $shipParts[] = $addr['shipping_country'];
                            $shipAddr = implode(', ', $shipParts);
                            if (!empty($shipAddr) && !in_array($shipAddr, $shipToAddresses)) {
                                $shipToAddresses[] = $shipAddr;
                            }
                        } else {
                            // No shipping address, use billing address
                            if (!in_array($billAddr, $shipToAddresses)) {
                                $shipToAddresses[] = $billAddr;
                            }
                        }
                    }
                }

                $defaultBillTo = !empty($billToAddresses) ? $billToAddresses[0] : '';
                $defaultShipTo = !empty($shipToAddresses) ? $shipToAddresses[0] : '';
                $invoiceAddressConn = $GLOBALS['conn'] ?? null;
                $firstBillAddr = (isset($customer_address) && is_array($customer_address))
                    ? (reset($customer_address) ?: null)
                    : null;
                $defaultBillToHtml = is_array($firstBillAddr)
                    ? invoice_format_order_info_address_display_html($firstBillAddr, 'billing', $invoiceAddressConn)
                    : '';
                $defaultShipToHtml = '';
                if (is_array($firstBillAddr)) {
                    $defaultShipToHtml = invoice_format_order_info_address_display_html($firstBillAddr, 'shipping', $invoiceAddressConn);
                    if ($defaultShipToHtml === '') {
                        $defaultShipToHtml = $defaultBillToHtml;
                    }
                }
                if ($defaultBillToHtml === '' && $defaultBillTo !== '') {
                    $defaultBillToHtml = htmlspecialchars($defaultBillTo, ENT_QUOTES, 'UTF-8');
                }
                if ($defaultShipToHtml === '' && $defaultShipTo !== '') {
                    $defaultShipToHtml = htmlspecialchars($defaultShipTo, ENT_QUOTES, 'UTF-8');
                }
                $showGSTContainer = isset($customer_address[0]['country']) && strtolower($customer_address[0]['country']) !== 'in';
                $addr0 = (isset($customer_address) && is_array($customer_address) && isset($customer_address[0]) && is_array($customer_address[0]))
                    ? $customer_address[0]
                    : null;
                $gstin = $addr0 !== null ? trim((string)($addr0['gstin'] ?? '')) : '';
                $showSupplyState = !$is_international && trim((string) $billingState) !== '';
                ?>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:p-6">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Bill To <span class="text-red-500">*</span></h2>
                        <p class="text-xs text-gray-500">Billing address used for GST and the invoice.</p>
                    </div>
                    <?php if (count($billToAddresses) > 0): ?>
                        <button type="button" onclick="openAddressSelector()" class="inline-flex items-center gap-1.5 rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-800 hover:bg-orange-100">
                            <i class="fas fa-pen" aria-hidden="true"></i> Change
                        </button>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="customer_address" id="billToSelect" value="<?= htmlspecialchars($defaultBillTo) ?>">
                <input type="hidden" name="vp_order_info_id" id="vp_order_info_id" value="<?= htmlspecialchars((string) ($customer_address[0]['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="billToDisplay" value="<?= htmlspecialchars($defaultBillTo) ?>">
                <p id="billToText" class="rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-700"><?= htmlspecialchars($defaultBillTo) !== '' ? htmlspecialchars($defaultBillTo) : 'No billing address found' ?></p>
                <?php if ($showSupplyState || $showGSTContainer): ?>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <?php if ($showSupplyState): ?>
                    <div id="supplystate" class="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-1.5 text-slate-700">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Supply state</span>
                        <span><?= htmlspecialchars((string) $billingState, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($showGSTContainer): ?>
                        <label id="applyGSTContainer" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5">
                            <input type="checkbox" id="applyGST" name="applyGST" value="1" class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="text-sm font-medium text-gray-700">Apply GST</span>
                        </label>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:p-6">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-800">Customer &amp; Ship To</h2>
                    <p class="text-xs text-gray-500">Customer details and delivery address for this invoice.</p>
                </div>
                <div class="mb-4 rounded-xl bg-gray-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Customer name <span class="text-red-500">*</span></div>
                    <div class="mt-1">
                        <?php if (isset($customer) && is_array($customer)): ?>
                            <span id="invoiceCustomerName" class="font-semibold text-slate-800"><?php echo htmlspecialchars($customer['name']); ?></span>
                            <input type="hidden" name="customer_id" value="<?php echo (int) $customer['id']; ?>">
                        <?php else: ?>
                            <span id="invoiceCustomerName" class="font-semibold text-red-500">Customer not found</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($gstin !== ''): ?>
                        <div class="mt-2 text-sm text-gray-600">Customer GST: <?php echo htmlspecialchars($gstin, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Ship To</div>
                    <p class="rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-700" id="shipToDisplay"><?= $defaultShipToHtml !== '' ? $defaultShipToHtml : 'Same as billing address' ?></p>
                    <input type="hidden" id="shipToDisplayValue" value="<?= htmlspecialchars($defaultShipTo) ?>">
                </div>
            </div>
        </div>

                <!-- Address Selector Modal -->
                <div id="addressSelectorModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onclick="closeAddressSelector()">
                    <div class="flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" onclick="event.stopPropagation()">
                        <div class="flex items-center justify-between border-b bg-white px-5 py-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Select delivery address</h2>
                                <p class="text-sm text-gray-500">Choose the Bill To / Ship To pair for this invoice.</p>
                            </div>
                            <button type="button" onclick="closeAddressSelector()" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800 text-2xl leading-none">&times;</button>
                        </div>
                        <div class="overflow-y-auto p-4">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                        <th class="rounded-l-lg p-3 w-1/12">Select</th>
                                        <th class="p-3 w-5/12">Bill To Address</th>
                                        <th class="rounded-r-lg p-3 w-5/12">Ship To Address</th>
                                    </tr>
                                </thead>
                                <tbody id="addressTableBody">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end gap-2 border-t bg-gray-50 px-5 py-4">
                            <button type="button" onclick="closeAddressSelector()" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300">Close</button>
                            <button type="button" onclick="applyAddressSelection()" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">Apply</button>
                        </div>
                    </div>
                </div>


        <!-- Item Table -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-[0px_10px_15px_-3px_#0000001A]">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Line items</h2>
                    <p class="text-sm text-gray-500">GST is calculated from supply state vs firm state. Box number maps to dispatch packing.</p>
                </div>
                <button type="button" id="addInvoiceItemBtn" class="inv-action-btn action-button inline-flex items-center justify-center gap-2 rounded-lg bg-[rgba(208,103,6,1)] px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">
                    <i class="fas fa-plus" aria-hidden="true"></i> Add Item
                </button>
            </div>
            <div class="overflow-x-auto p-4">
            <table class="inv-table w-full min-w-[980px] border-separate" id="invoiceTable" style="border-spacing: 0 8px;">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2" colspan="2">Item</th>
                        <th class="px-3 py-2">Box</th>
                        <th class="px-3 py-2">HSN</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Unit Price</th>
                        <th class="px-3 py-2 text-right">Discount</th>
                        <th class="px-3 py-2 text-right">CGST %</th>
                        <th class="px-3 py-2 text-right">SGST %</th>
                        <th class="px-3 py-2 text-right">IGST %</th>
                        <th class="px-3 py-2 text-right">Amount</th>
                        <th class="px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    <?php
                    if (isset($data) && is_array($data)) {
                        foreach ($data as $index => $item):
                            $itemCurrency = $item['currency'] ?? 'INR';
                            $itemCurrencyPrefix = ($itemCurrency === 'INR') ? '₹' : htmlspecialchars((string) $itemCurrency, ENT_QUOTES, 'UTF-8') . ' ';
                            $itemImage = trim((string) ($item['image'] ?? ''));
                    ?>
                            <tr class="bg-gray-50">
                                <td class="rounded-l-xl px-3 py-3 font-medium text-gray-500">
                                    <input type="hidden" name="order_number[]" value="<?= htmlspecialchars((string) $item['order_number'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="item_code[]" value="<?= htmlspecialchars((string) $item['item_code'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="gst[]" value="<?= htmlspecialchars((string) $item['gst'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="tax_rate[]" value="<?= htmlspecialchars((string) $item['gst'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="currency[]" value="<?php echo htmlspecialchars((string) ($item['currency'] ?? 'INR'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="image_url[]" value="<?= htmlspecialchars((string) ($item['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="groupname[]" value="<?= htmlspecialchars((string) ($item['groupname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php echo $index + 1; ?>
                                </td>
                                <td class="px-3 py-3 font-medium text-slate-800">
                                    <span><?= htmlspecialchars((string) $item['sku'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php
                                    $lineOrderNo = trim((string) ($item['order_number'] ?? ''));
                                    if ($lineOrderNo !== ''):
                                        $lineOrderUrl = base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . rawurlencode($lineOrderNo));
                                    ?>
                                        <div class="mt-1">
                                            <a href="<?= htmlspecialchars($lineOrderUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-orange-700 hover:underline" title="View order details"><?= htmlspecialchars($lineOrderNo, ENT_QUOTES, 'UTF-8') ?></a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3" colspan="2">
                                    <div class="flex items-start gap-3">
                                        <?php if ($itemImage !== ''): ?>
                                            <img src="<?= htmlspecialchars($itemImage, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200">
                                        <?php endif; ?>
                                        <span class="leading-snug"><?= htmlspecialchars($item['title'] ?? '') ?></span>
                                    </div>
                                    <input type="hidden" name="item_name[]" value="<?= htmlspecialchars($item['title'] ?? '') ?>" required>
                                </td>
                                <td class="p-2">
                                    <input type="text" name="box_no[]" class="w-16 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-sm" value="1" required>
                                </td>
                                <td class="px-3 py-3 text-gray-600"><span><?= htmlspecialchars((string) $item['hsn'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <input type="hidden" name="hsn[]" value="<?= htmlspecialchars((string) $item['hsn'], ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td class="px-3 py-3 text-right"><span><?= htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8') ?></span>
                                    <input type="hidden" name="quantity[]" value="<?= htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums"><span><?= $item['unit_price'] ? $itemCurrencyPrefix . $item['unit_price'] : '0.00' ?></span>
                                    <input type="hidden" name="unit_price[]" value="<?= htmlspecialchars((string) ($item['unit_price'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td class="px-3 py-3 text-right text-gray-500"><span>0%</span>
                                    <input type="hidden" name="discount[]" value="0">
                                </td>
                                <td class="px-3 py-3 text-right"><span>0%</span>
                                    <input type="hidden" name="cgst[]" value="0">
                                </td>
                                <td class="px-3 py-3 text-right"><span>0%</span>
                                    <input type="hidden" name="sgst[]" value="0">
                                </td>
                                <td class="px-3 py-3 text-right"><span>0%</span>
                                    <input type="hidden" name="igst[]" value="0">
                                </td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums"><span><?= $item['unit_price'] ? $itemCurrencyPrefix . $item['unit_price'] : '0.00' ?></span>
                                    <input type="hidden" name="line_total[]" step="0.01">
                                </td>
                                <td class="rounded-r-xl px-3 py-3 text-center">
                                    <button type="button" onclick="removeRow(this)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700" title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                    <?php endforeach;
                    } ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Totals Section -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:p-6">
                <h2 class="mb-3 text-base font-semibold text-slate-800">GST breakdown</h2>
                <div id="taxTotalsDisplay" class="space-y-2 text-sm text-gray-600">
                    <p class="text-gray-400">Totals will appear after GST is calculated.</p>
                </div>
            </div>
            <div class="rounded-2xl border border-orange-100 bg-white p-5 shadow-[0px_10px_15px_-3px_#0000001A] md:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-800">Invoice total</h2>
                    <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-800 ring-1 ring-orange-200">
                        <span class="invoice-currency-code-text"><?php echo htmlspecialchars((string) $invoiceCurrency, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-gray-600">Subtotal</span>
                        <div class="flex items-center gap-1.5">
                            <span class="invoice-currency-prefix-subtotal text-sm font-medium text-gray-500"><?php echo $invoiceCurrency === 'INR' ? '₹' : htmlspecialchars((string)$invoiceCurrency, ENT_QUOTES, 'UTF-8') . ' '; ?></span>
                            <input type="number" name="subtotal" id="subtotal" step="0.01" class="w-40 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-sm font-medium tabular-nums" readonly>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-gray-600">Tax amount</span>
                        <div class="flex items-center gap-1.5">
                            <span class="invoice-currency-prefix-tax text-sm font-medium text-gray-500"><?php echo $invoiceCurrency === 'INR' ? '₹' : htmlspecialchars((string)$invoiceCurrency, ENT_QUOTES, 'UTF-8') . ' '; ?></span>
                            <input type="number" name="tax_amount" id="tax_amount" step="0.01" class="w-40 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-sm font-medium tabular-nums" readonly>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-gray-600">Discount</span>
                        <div class="flex items-center gap-1.5">
                            <span class="invoice-currency-prefix-discount text-sm font-medium text-gray-500"><?php echo $invoiceCurrency === 'INR' ? '₹' : htmlspecialchars((string)$invoiceCurrency, ENT_QUOTES, 'UTF-8') . ' '; ?></span>
                            <input type="number" name="discount_amount" id="discount_amount" step="0.01" class="w-40 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-sm font-medium tabular-nums" value="0" oninput="calculateTotals()" readonly>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-orange-100 pt-4">
                        <span class="text-base font-bold text-slate-800">Total amount</span>
                        <div class="flex items-center gap-1.5">
                            <span class="invoice-currency-prefix-total text-base font-bold text-orange-800"><?php echo $invoiceCurrency === 'INR' ? '₹' : htmlspecialchars((string)$invoiceCurrency, ENT_QUOTES, 'UTF-8') . ' '; ?></span>
                            <input type="number" name="total_amount" id="total_amount" step="0.01" class="w-40 rounded-lg border-2 border-orange-400 bg-orange-50 px-3 py-2 text-right text-base font-bold tabular-nums text-orange-800" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="sticky bottom-3 z-20 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between form-actions">
            <p class="text-sm text-gray-500">Preview the tax invoice, then create it. Dispatch is available for domestic invoices.</p>
            <div class="flex flex-wrap justify-end gap-2">
            <input type="hidden" name="pos_flag" value="<?php echo htmlspecialchars((string) $pos_flag, ENT_QUOTES, 'UTF-8'); ?>">
            <a href="<?php echo base_url('?page=invoices&action=cancel_create'); ?>" class="rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-300">Cancel</a>
            <button type="button" onclick="previewInvoice()" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Preview</button>
            <button type="submit" id="createInvoiceButton" class="rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">Create Invoice</button>
            <?php if($is_international === false): ?>
                <button type="button" id="createAndDispatchButton" onclick="createAndDispatch()" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Create &amp; Dispatch</button>
            <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Invoice Preview Modal -->
<div id="invoicePreviewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onclick="closePreviewModal()">
    <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b bg-white px-5 py-4">
            <h2 class="text-lg font-bold text-slate-800">Invoice preview</h2>
            <button type="button" onclick="closePreviewModal()" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800 text-2xl leading-none">&times;</button>
        </div>
        <div id="invoicePreviewContent" class="overflow-y-auto p-4"></div>
        <div class="flex justify-end gap-2 border-t bg-gray-50 px-5 py-4">
            <button type="button" onclick="closePreviewModal()" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300">Close</button>
            <button type="button" onclick="window.print()" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">Print</button>
        </div>
    </div>
</div>
<!-- Order Item Modal -->
<div id="orderModal" class="fixed inset-0 z-50 items-center justify-center bg-black/40 p-4" style="display:none;">
    <div class="relative w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl">
        <button type="button" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full text-2xl font-bold text-gray-500 hover:bg-gray-100 hover:text-black" id="closeOrderModal">&times;</button>
        <h2 class="mb-1 text-lg font-bold text-slate-800">Select order item</h2>
        <p class="mb-4 text-sm text-gray-500">Search by order ID, SKU, or title to add another line to this invoice.</p>
        <input type="text" id="orderSearch" class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200" placeholder="Search with order id, item code, or title...">
        <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="p-3">Order ID</th>
                        <th class="p-3">SKU</th>
                        <th class="p-3">Title</th>
                        <th class="p-3 text-right">Price</th>
                        <th class="p-3 text-center">Qty</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="orderItemsTableBody">
                    <!-- Dynamic rows here -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    const invoiceOrderDetailsUrlBase = <?php echo json_encode(base_url('?page=orders&action=get_order_details_html&type=outer&order_number=')); ?>;

    function invoiceOrderDetailsUrl(orderNumber) {
        const orderNo = String(orderNumber || '').trim();
        if (!orderNo) {
            return '';
        }
        return invoiceOrderDetailsUrlBase + encodeURIComponent(orderNo);
    }

    function invoiceOrderDetailsLink(orderNumber) {
        const orderNo = String(orderNumber || '').trim();
        const url = invoiceOrderDetailsUrl(orderNo);
        if (!orderNo || !url) {
            return '';
        }
        const safeNo = String(orderNo).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-orange-700 hover:underline" title="View order details" onclick="event.stopPropagation()">${safeNo}</a>`;
    }

    function roundToTwo(num) {
        return Math.round(num * 100) / 100;
    }

    // Store firm state for GST calculation
    const firmState = <?php echo $firmStateJS; ?>;

    // Store address data for modal
    const addressData = <?php echo json_encode(array_map(function ($addr) {
                            $invoiceAddressConn = $GLOBALS['conn'] ?? null;
                            $billParts = [];
                            if (!empty($addr['first_name'])) $billParts[] = $addr['first_name'] . ' ' . $addr['last_name'];
                            if (!empty($addr['address_line1'])) $billParts[] = $addr['address_line1'];
                            if (!empty($addr['address_line2'])) $billParts[] = $addr['address_line2'];
                            if (!empty($addr['city'])) $billParts[] = $addr['city'];
                            if (!empty($addr['state'])) $billParts[] = $addr['state'];
                            if (!empty($addr['zipcode'])) $billParts[] = $addr['zipcode'];
                            if (!empty($addr['country'])) $billParts[] = $addr['country'];
                            $billAddr = implode(', ', $billParts);

                            $shipParts = [];
                            if (!empty($addr['shipping_address_line1'])) {
                                if (!empty($addr['shipping_first_name'])) $shipParts[] = $addr['shipping_first_name'] . ' ' . $addr['shipping_last_name'];
                                if (!empty($addr['shipping_address_line1'])) $shipParts[] = $addr['shipping_address_line1'];
                                if (!empty($addr['shipping_address_line2'])) $shipParts[] = $addr['shipping_address_line2'];
                                if (!empty($addr['shipping_city'])) $shipParts[] = $addr['shipping_city'];
                                if (!empty($addr['shipping_state'])) $shipParts[] = $addr['shipping_state'];
                                if (!empty($addr['shipping_zipcode'])) $shipParts[] = $addr['shipping_zipcode'];
                                if (!empty($addr['shipping_country'])) $shipParts[] = $addr['shipping_country'];
                            }
                            $shipAddr = implode(', ', $shipParts);
                            $billHtml = invoice_format_order_info_address_display_html($addr, 'billing', $invoiceAddressConn);
                            $shipHtml = invoice_format_order_info_address_display_html($addr, 'shipping', $invoiceAddressConn);
                            if ($shipHtml === '') {
                                $shipHtml = $billHtml;
                            }

                            return [
                                'id' => $addr['id'],
                                'order_number' => $addr['order_number'] ?? '',
                                'bill_to' => $billAddr,
                                'ship_to' => $shipAddr,
                                'bill_html' => $billHtml,
                                'ship_html' => $shipHtml,
                                'state' => $addr['state'] ?? ''
                            ];
                        }, $customer_address)) ?>;

    // Function to determine tax type based on states
    const invoiceIsInternational = <?php echo !empty($is_international) ? 'true' : 'false'; ?>;
    let currentBillingState = <?php echo json_encode((string) $billingState, JSON_UNESCAPED_UNICODE); ?>;

    function calculateGSTType(billingState) {
        const state = String(billingState == null ? currentBillingState : billingState).trim();
        const firm = String(firmState || '').trim();
        if (!state || !firm) {
            // Export / missing Indian state: Apply GST as IGST
            return (invoiceIsInternational || document.getElementById('applyGST')) ? 'different' : null;
        }
        return (state.toUpperCase() === firm.toUpperCase()) ? 'same' : 'different';
    }

    function refreshCurrentInvoiceGst() {
        refreshInvoiceGstFields(calculateGSTType(currentBillingState));
    }

    function openAddressSelector() {
        const modal = document.getElementById('addressSelectorModal');
        const tableBody = document.getElementById('addressTableBody');
        const currentAddressId = document.getElementById('vp_order_info_id').value;

        tableBody.innerHTML = '';

        addressData.forEach((addr, idx) => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-100 hover:bg-orange-50/40';
            row.innerHTML = `
            <td class="p-3 text-center align-top">
                <input type="radio" name="addressRadio" value="${addr.id}" data-bill-to="${addr.bill_to}" data-ship-to="${addr.ship_to}" ${addr.id == currentAddressId ? 'checked' : ''} class="h-4 w-4 text-orange-600">
                ${addr.order_number ? `<div class="mt-1">${invoiceOrderDetailsLink(addr.order_number)}</div>` : ''}
            </td>
            <td class="p-3 text-sm text-gray-700 leading-relaxed">${addr.bill_html || addr.bill_to}</td>
            <td class="p-3 text-sm text-gray-700 leading-relaxed">${addr.ship_html || addr.ship_to || '<span class="text-gray-400">No shipping address</span>'}</td>
        `;
            tableBody.appendChild(row);
        });

        modal.classList.remove('hidden');
    }

    function closeAddressSelector() {
        document.getElementById('addressSelectorModal').classList.add('hidden');
    }

    function invoiceNotify(message, type) {
        if (window.showAlert) {
            showAlert(message, type || 'info');
        } else {
            console.warn(message);
        }
    }

    function invoiceCurrencyCode() {
        const first = document.querySelector('#invoiceTable input[name="currency[]"]');
        const currency = first ? String(first.value || '').trim() : '';
        return currency || <?php echo json_encode((string)$invoiceCurrency, JSON_UNESCAPED_UNICODE); ?> || 'INR';
    }

    function invoiceCurrencyPrefix() {
        const code = invoiceCurrencyCode();
        return code === 'INR' ? '₹' : code + ' ';
    }

    function refreshInvoiceCurrencyDisplay() {
        const prefix = invoiceCurrencyPrefix();
        const code = invoiceCurrencyCode();
        document.querySelectorAll('.invoice-currency-prefix-subtotal, .invoice-currency-prefix-tax, .invoice-currency-prefix-discount, .invoice-currency-prefix-total').forEach(function(el) {
            el.textContent = prefix;
        });
        document.querySelectorAll('.invoice-currency-code-text').forEach(function(el) {
            el.textContent = code;
        });
    }

    function applyAddressSelection() {
        const selectedRadio = document.querySelector('input[name="addressRadio"]:checked');

        if (!selectedRadio) {
            invoiceNotify('Please select an address', 'warning');
            return;
        }

        const addressId = selectedRadio.value;
        const billTo = selectedRadio.getAttribute('data-bill-to');
        const shipTo = selectedRadio.getAttribute('data-ship-to');
        const selectedAddress = addressData.find(a => a.id == addressId);

        // Update form fields
        document.getElementById('vp_order_info_id').value = addressId;
        document.getElementById('billToSelect').value = billTo;
        document.getElementById('billToDisplay').value = billTo;

        const billToText = document.getElementById('billToText');
        if (billToText) {
            billToText.textContent = billTo || 'No billing address found';
        }

        const shipToDisplay = document.getElementById('shipToDisplay');
        if (shipToDisplay) {
            shipToDisplay.innerHTML = (selectedAddress && selectedAddress.ship_html)
                ? selectedAddress.ship_html
                : (shipTo || '');
        }
        const shipToHidden = document.getElementById('shipToDisplayValue');
        if (shipToHidden) {
            shipToHidden.value = shipTo || '';
        }

        // Auto-populate GST fields based on state comparison
        if (selectedAddress) {
            const gstType = calculateGSTType(selectedAddress.state);
            currentBillingState = selectedAddress.state || '';
            refreshInvoiceGstFields(gstType);
            const supplyState = document.getElementById('supplystate');
            const supplyValue = supplyState ? supplyState.querySelectorAll('span')[1] : null;
            if (supplyValue) {
                supplyValue.textContent = selectedAddress.state || '';
            }
        }

        closeAddressSelector();
    }


    function previewInvoice() {
        const formData = new FormData(document.getElementById('create_invoice'));

        // Collect item data
        const items = [];
        document.querySelectorAll('#invoiceTable tbody tr').forEach((row, idx) => {
            items.push({
                order_number: row.querySelector('input[name="order_number[]"]')?.value || '',
                box_no: row.querySelector('input[name="box_no[]"]')?.value || '',
                item_code: row.querySelector('input[name="item_code[]"]')?.value || '',
                item_name: row.querySelector('input[name="item_name[]"]')?.value || '',
                hsn: row.querySelector('input[name="hsn[]"]')?.value || '',
                quantity: row.querySelector('input[name="quantity[]"]')?.value || 0,
                unit_price: row.querySelector('input[name="unit_price[]"]')?.value || 0,
                cgst: row.querySelector('input[name="cgst[]"]')?.value || 0,
                sgst: row.querySelector('input[name="sgst[]"]')?.value || 0,
                igst: row.querySelector('input[name="igst[]"]')?.value || 0,
                tax_amount: row.querySelector('input[name="tax_amount[]"]')?.value || 0,
                line_total: row.querySelector('input[name="line_total[]"]')?.value || 0,
                currency: row.querySelector('input[name="currency[]"]')?.value || 'INR',
                image_url: row.querySelector('input[name="image_url[]"]')?.value || '',
                groupname: row.querySelector('input[name="groupname[]"]')?.value || ''
            });
        });

        if (items.length === 0) {
            invoiceNotify('Please add at least one item to preview', 'warning');
            return;
        }

        // Get selected address
        const vp_order_info_id = document.getElementById('vp_order_info_id').value;
        //const vpAddressInfoId = billToSelect && billToSelect.tagName === 'SELECT' ? billToSelect.value : '';

        const previewData = {
            invoice_date: formData.get('invoice_date') || new Date().toISOString().split('T')[0],
            customer_id: formData.get('customer_id') || 0,
            vp_order_info_id: vp_order_info_id || 0,
            subtotal: document.getElementById('subtotal')?.value || 0,
            tax_amount: document.getElementById('tax_amount')?.value || 0,
            discount_amount: document.getElementById('discount_amount')?.value || 0,
            total_amount: document.getElementById('total_amount')?.value || 0,
            status: formData.get('status') || 'draft',
            items: items
        };

        // Send to server for preview using template
        fetch('<?php echo base_url('?page=invoices&action=preview'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(previewData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display the HTML preview in modal
                    const modal = document.getElementById('invoicePreviewModal');
                    const previewContent = document.getElementById('invoicePreviewContent');

                    // Set the HTML content from the tax invoice template
                    previewContent.innerHTML = `<div style="max-height: 500px; overflow-y: auto; background: white;">${data.html}</div>`;

                    modal.classList.remove('hidden');
                } else {
                    invoiceNotify('Error generating preview: ' + data.message, 'error');
                }
            })
            .catch(err => {
                console.error('Preview error:', err);
                invoiceNotify('Failed to generate preview', 'error');
            });
    }


    function closePreviewModal() {
        document.getElementById('invoicePreviewModal').classList.add('hidden');
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        calculateTotals();
    }

    function shouldApplyInvoiceGst() {
        const applyGSTCheckbox = document.getElementById('applyGST');
        if (!applyGSTCheckbox) {
            return true;
        }
        return applyGSTCheckbox.checked;
    }

    function refreshInvoiceGstFields(gstType) {
        if (shouldApplyInvoiceGst()) {
            updateGSTFields(gstType);
            return;
        }
        if (typeof clearGSTFields === 'function') {
            clearGSTFields();
            return;
        }
        calculateTotals();
    }

    function updateGSTFields(gstType) {
        const rows = document.querySelectorAll('#invoiceTable tbody tr');

        rows.forEach(row => {
            const gstValue = parseFloat(
                row.querySelector('input[name="gst[]"]')?.value
                || row.querySelector('input[name="tax_rate[]"]')?.value
                || 0
            ) || 0;
            const cgstInput = row.querySelector('input[name="cgst[]"]');
            const sgstInput = row.querySelector('input[name="sgst[]"]');
            const igstInput = row.querySelector('input[name="igst[]"]');
            const taxRateInput = row.querySelector('input[name="tax_rate[]"]');
            if (gstType === 'same') {
                // Same state: Split GST between CGST and SGST (50% each)
                const halfGst = gstValue / 2;
                if (cgstInput) cgstInput.value = halfGst.toFixed(2);
                if (sgstInput) sgstInput.value = halfGst.toFixed(2);
                if (igstInput) igstInput.value = '0';
            } else {
                // Different state / export: All GST goes to IGST
                if (cgstInput) cgstInput.value = '0';
                if (sgstInput) sgstInput.value = '0';
                if (igstInput) igstInput.value = gstValue.toFixed(2);
            }
            if (taxRateInput) {
                taxRateInput.value = gstValue.toFixed(2);
            }

            // Update display spans
            const cgstSpan = row.querySelector('input[name="cgst[]"]')?.previousElementSibling;
            const sgstSpan = row.querySelector('input[name="sgst[]"]')?.previousElementSibling;
            const igstSpan = row.querySelector('input[name="igst[]"]')?.previousElementSibling;

            if (cgstSpan) cgstSpan.textContent = (cgstInput?.value || '0') + '%';
            if (sgstSpan) sgstSpan.textContent = (sgstInput?.value || '0') + '%';
            if (igstSpan) igstSpan.textContent = (igstInput?.value || '0') + '%';
        });

        calculateTotals();
    }

    function calculateTotals() {
        const rows = document.querySelectorAll('#invoiceTable tbody tr');
        let subtotal = 0;
        let totalTax = 0;
        let totalsgst = 0;
        let totalcgst = 0;
        let totaligst = 0;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="quantity[]"]')?.value) || 0;
            const unitPrice = parseFloat(row.querySelector('input[name="unit_price[]"]')?.value) || 0;
            const cgst = parseFloat(row.querySelector('input[name="cgst[]"]')?.value) || 0;
            const sgst = parseFloat(row.querySelector('input[name="sgst[]"]')?.value) || 0;
            const igst = parseFloat(row.querySelector('input[name="igst[]"]')?.value) || 0;

            const lineTotal = roundToTwo(qty * unitPrice);
            const lineTax = roundToTwo((lineTotal * (cgst + sgst + igst)) / 100);

            // Update line total input and display
            const lineTotalInput = row.querySelector('input[name="line_total[]"]');
            if (lineTotalInput) {
                lineTotalInput.value = lineTotal.toFixed(2);
                // Update display span in parent td
                const lineTotalDisplay = lineTotalInput.parentElement.querySelector('span');
                if (lineTotalDisplay) {
                    lineTotalDisplay.textContent = invoiceCurrencyPrefix() + lineTotal.toFixed(2);
                }
                console.log('Updated line total for row:', row, 'Line Total:', lineTotal);
            }

            // Update CGST input and display
            const cgstInput = row.querySelector('input[name="cgst[]"]');
            if (cgstInput) {
                const cgstDisplay = cgstInput.parentElement.querySelector('span');
                if (cgstDisplay) {
                    cgstDisplay.textContent = cgst.toFixed(2) + '%';
                }
            }

            // Update SGST input and display
            const sgstInput = row.querySelector('input[name="sgst[]"]');
            if (sgstInput) {
                const sgstDisplay = sgstInput.parentElement.querySelector('span');
                if (sgstDisplay) {
                    sgstDisplay.textContent = sgst.toFixed(2) + '%';
                }
            }

            // Update IGST input and display
            const igstInput = row.querySelector('input[name="igst[]"]');
            if (igstInput) {
                const igstDisplay = igstInput.parentElement.querySelector('span');
                if (igstDisplay) {
                    igstDisplay.textContent = igst.toFixed(2) + '%';
                }
            }

            subtotal += lineTotal;
            totalTax += lineTax;
            totalsgst += roundToTwo((lineTotal * sgst) / 100);
            totalcgst += roundToTwo((lineTotal * cgst) / 100);
            totaligst += roundToTwo((lineTotal * igst) / 100);
        });

        const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
        const totalAmount = roundToTwo(subtotal + totalTax - discount);

        document.getElementById('subtotal').value = roundToTwo(subtotal).toFixed(2);
        document.getElementById('tax_amount').value = roundToTwo(totalTax).toFixed(2);
        document.getElementById('total_amount').value = totalAmount.toFixed(2);

        refreshInvoiceCurrencyDisplay();

        // Update tax totals display
        const taxTotalsDisplay = document.getElementById('taxTotalsDisplay');
        const prefix = invoiceCurrencyPrefix();
        taxTotalsDisplay.innerHTML = `
        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-600">CGST total</span>
            <span class="font-semibold tabular-nums">${prefix}${roundToTwo(totalcgst).toFixed(2)}</span>
        </div>
        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-600">SGST total</span>
            <span class="font-semibold tabular-nums">${prefix}${roundToTwo(totalsgst).toFixed(2)}</span>
        </div>
        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-600">IGST total</span>
            <span class="font-semibold tabular-nums">${prefix}${roundToTwo(totaligst).toFixed(2)}</span>
        </div>
    `;
    }

    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', function() {
        const transportModeFields = document.getElementById('transportModeFields');
        const transportIdFields = document.getElementById('transportIdFields');
        const transporterIdSelect = document.getElementById('invoice_transporter_id');
        const transporterNameInput = document.getElementById('invoice_transporter_name');
        function applyTransportSelection(value) {
            const useTransportId = value === 'id';
            transportModeFields?.classList.toggle('hidden', useTransportId);
            transportIdFields?.classList.toggle('hidden', !useTransportId);
            transportModeFields?.querySelectorAll('input, select, textarea').forEach(function(el) {
                el.disabled = useTransportId;
            });
            transportIdFields?.querySelectorAll('input, select, textarea').forEach(function(el) {
                el.disabled = !useTransportId;
            });
        }
        document.querySelectorAll('[data-transport-selection]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                applyTransportSelection(this.value);
            });
        });
        const selectedTransport = document.querySelector('[data-transport-selection]:checked');
        applyTransportSelection(selectedTransport ? selectedTransport.value : 'id');
        transporterIdSelect?.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (transporterNameInput) {
                transporterNameInput.value = option?.dataset.transporterName || '';
            }
        });

        // Set initial GST based on default billing state and Apply GST checkbox
        refreshCurrentInvoiceGst();
        const applyGSTCheckbox = document.getElementById('applyGST');
        if (applyGSTCheckbox) {
            applyGSTCheckbox.dataset.bound = '1';
            applyGSTCheckbox.addEventListener('change', refreshCurrentInvoiceGst);
        }
        initShippingPortMasterFields();
        initFinalDestinationCountryField();
    });

    function shippingPortTypeFromPreCarriage(selectEl) {
        const selected = selectEl?.options?.[selectEl.selectedIndex];
        const fromData = (selected?.dataset?.portType || '').toLowerCase();
        if (fromData) {
            return fromData;
        }
        const value = String(selectEl?.value || '').toLowerCase();
        if (value.indexOf('sea') !== -1) return 'sea';
        if (value.indexOf('inland') !== -1) return 'inland';
        if (value.indexOf('dry') !== -1) return 'dry';
        return 'air';
    }

    function syncShippingPortCode() {
        const loading = document.getElementById('port_of_loading');
        const shipping = document.getElementById('shipping_port');
        if (!loading || !shipping || loading.tagName !== 'SELECT') {
            return;
        }
        const selected = loading.options[loading.selectedIndex];
        const code = selected?.dataset?.portCode || '';
        if (code) {
            shipping.value = code;
        }
    }

    function filterPortOfLoadingByType(keepCurrent) {
        const preCarriage = document.getElementById('pre_carriage_by');
        const loading = document.getElementById('port_of_loading');
        if (!preCarriage || !loading || loading.tagName !== 'SELECT') {
            return;
        }
        const type = shippingPortTypeFromPreCarriage(preCarriage);
        const current = loading.value;
        let firstVisible = '';
        Array.from(loading.options).forEach(function (opt) {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            const match = (opt.dataset.portType || '') === type;
            opt.hidden = !match;
            opt.disabled = !match;
            if (match && firstVisible === '') {
                firstVisible = opt.value;
            }
        });
        const selected = loading.options[loading.selectedIndex];
        if (!keepCurrent || !selected || selected.hidden || !selected.value) {
            loading.value = firstVisible;
        } else if (current) {
            loading.value = current;
        }
        syncShippingPortCode();
    }

    function initShippingPortMasterFields() {
        const preCarriage = document.getElementById('pre_carriage_by');
        const loading = document.getElementById('port_of_loading');
        if (!preCarriage || !loading || loading.tagName !== 'SELECT') {
            return;
        }
        filterPortOfLoadingByType(true);
        preCarriage.addEventListener('change', function () {
            filterPortOfLoadingByType(false);
        });
        loading.addEventListener('change', syncShippingPortCode);
    }

    function syncShippingCountryCode() {
        const country = document.getElementById('country_of_final_destination');
        const codeInput = document.getElementById('shipping_country_code');
        if (!country || !codeInput || country.tagName !== 'SELECT') {
            return;
        }
        const selected = country.options[country.selectedIndex];
        const code = selected?.dataset?.countryCode || '';
        if (code) {
            codeInput.value = code;
        }
    }

    function initFinalDestinationCountryField() {
        const country = document.getElementById('country_of_final_destination');
        if (!country || country.tagName !== 'SELECT') {
            return;
        }
        syncShippingCountryCode();
        country.addEventListener('change', syncShippingCountryCode);
    }

    // Validate international section before submitting
    function validateInternationalSection() {
        const internationalSection = document.getElementById('internationalSection');
        if (!internationalSection || getComputedStyle(internationalSection).display === 'none') {
            return true;
        }

        const requiredFields = [{
                id: 'pre_carriage_by',
                label: 'Pre Carriage By'
            },
            {
                id: 'port_of_loading',
                label: 'Port of Loading'
            },
            {
                id: 'port_of_discharge',
                label: 'Port of Discharge'
            },
            {
                id: 'country_of_origin',
                label: 'Country of Origin'
            },
            {
                id: 'country_of_final_destination',
                label: 'Country of Final Destination'
            },
            {
                id: 'final_destination',
                label: 'Final Destination'
            },
            {
                id: 'usd_export_rate',
                label: 'USD Export Rate'
            },
            {
                id: 'shipping_bill_number',
                label: 'Shipping Bill Number'
            },
            {
                id: 'shipping_bill_date',
                label: 'Shipping Bill Date'
            },
            {
                id: 'shipping_port',
                label: 'Shipping Port Code'
            },
            {
                id: 'shipping_ref_clm',
                label: 'Shipping Ref CLM'
            },
            {
                id: 'shipping_currency',
                label: 'Shipping Currency'
            },
            {
                id: 'shipping_country_code',
                label: 'Shipping Country Code'
            },
            {
                id: 'shipping_exp_duty',
                label: 'Shipping Exp Duty'
            }
        ];

        for (const fieldInfo of requiredFields) {
            const field = document.getElementById(fieldInfo.id);
            if (!field || String(field.value).trim() === '') {
                const message = fieldInfo.label + ' is required for international invoices';
                invoiceNotify(message, 'error');
                field?.focus();
                return false;
            }
        }

        const usdExportRate = parseFloat(document.getElementById('usd_export_rate').value);
        if (isNaN(usdExportRate) || usdExportRate <= 0) {
            const message = 'USD Export Rate must be a valid positive number';
            invoiceNotify(message, 'error');
            document.getElementById('usd_export_rate').focus();
            return false;
        }

        const shippingExpDuty = parseFloat(document.getElementById('shipping_exp_duty').value);
        if (isNaN(shippingExpDuty) || shippingExpDuty < 0) {
            const message = 'Shipping Exp Duty must be a valid number';
            invoiceNotify(message, 'error');
            document.getElementById('shipping_exp_duty').focus();
            return false;
        }

        return true;
    }

    // Form submission
    document.getElementById('create_invoice').addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateInternationalSection()) {
            return;
        }

        if (typeof syncShippingPortCode === 'function') {
            syncShippingPortCode();
        }
        if (typeof syncShippingCountryCode === 'function') {
            syncShippingCountryCode();
        }

        const formData = new FormData(this);
        // Disable button and show loading state
        const submitBtn = document.getElementById('createInvoiceButton');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="animate-spin">⏳</span> Processing...';

        // Validate customer name
        const customerName = document.getElementById('invoiceCustomerName');

        if (!customerName || !customerName.textContent.trim() || customerName.textContent.includes('****') || customerName.textContent.includes('not found')) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Create Invoice';
            invoiceNotify('Please select a valid customer', 'error');
            return;
        }

        fetch('<?php echo base_url('?page=invoices&action=create_post'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Invoice';

                    // Check if IRN generation failed
                    if (data.irn_generated === false && data.irn_error_message) {
                        // Show IRN error message and regenerate button
                        showAlert('Invoice created but IRN generation failed: ' + data.irn_error_message, 'warning');

                        // Add regenerate IRN button
                        const regenerateBtn = document.createElement('span');
                        regenerateBtn.innerHTML = 'Regenerate IRN';
                        regenerateBtn.className = 'ml-4 bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded';
                        regenerateBtn.onclick = function() {
                            regenerateIrn(data.invoice_id);
                        };

                        // Find the alert container and add the button
                        const alertContainer = document.querySelector('.form-actions') || document.body;
                        alertContainer.appendChild(regenerateBtn);

                        return; // Don't proceed with PDF generation and redirect
                    }

                    localStorage.removeItem('selected_po_orders');
                    showAlert('Invoice created successfully!', 'success');
                    //dispatch after success
                    const dispatchField = document.querySelector('input[name="dispatch_after_creation"]');
                    //redirect to ?page=dispatch&action=create&invoice_id=
                    if (dispatchField && dispatchField.checked) {
                        window.location.href = '<?php echo base_url('?page=dispatch&action=create&invoice_id='); ?>' + data.invoice_id;
                        return;
                    }
                    // Generate PDF after a short delay
                    setTimeout(() => {
                        fetch('<?php echo base_url('?page=invoices&action=generate_pdf'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    invoice_id: data.invoice_id
                                })
                            })
                            .then(response => response.blob())
                            .then(blob => {
                                const url = window.URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = 'invoice_' + data.invoice_id + '.pdf';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                window.URL.revokeObjectURL(url);

                                //if international invoice, redirect to dispatch page
                                if (data.is_international) {
                                    window.location.href = '<?php echo base_url('?page=dispatch&action=create&invoice_id='); ?>' + data.invoice_id;
                                    return;
                                }
                                // Redirect to invoice view
                                setTimeout(() => {
                                    window.location.href = '<?php echo base_url('?page=orders&action=list'); ?>';
                                }, 1000);
                            })
                            .catch(err => {
                                console.error('PDF generation error:', err);
                                window.location.href = '<?php echo base_url('?page=orders&action=list'); ?>';
                            });
                    }, 1000);
                } else {
                    invoiceNotify('Error: ' + data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Invoice';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                invoiceNotify('Network error occurred', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Invoice';
            });
    });

    // Add event listener for applyGST checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const applyGSTCheckbox = document.getElementById('applyGST');
        if (applyGSTCheckbox && applyGSTCheckbox.dataset.bound !== '1') {
            applyGSTCheckbox.dataset.bound = '1';
            applyGSTCheckbox.addEventListener('change', refreshCurrentInvoiceGst);
        }
    });

    // New function to clear GST fields
    function clearGSTFields() {
        const rows = document.querySelectorAll('#invoiceTable tbody tr');

        rows.forEach(row => {
            const cgstInput = row.querySelector('input[name="cgst[]"]');
            const sgstInput = row.querySelector('input[name="sgst[]"]');
            const igstInput = row.querySelector('input[name="igst[]"]');

            if (cgstInput) cgstInput.value = '0';
            if (sgstInput) sgstInput.value = '0';
            if (igstInput) igstInput.value = '0';
            const taxRateInput = row.querySelector('input[name="tax_rate[]"]');
            if (taxRateInput) taxRateInput.value = '0';

            // Update display spans
            const cgstSpan = row.querySelector('input[name="cgst[]"]')?.previousElementSibling;
            const sgstSpan = row.querySelector('input[name="sgst[]"]')?.previousElementSibling;
            const igstSpan = row.querySelector('input[name="igst[]"]')?.previousElementSibling;

            if (cgstSpan) cgstSpan.textContent = '0%';
            if (sgstSpan) sgstSpan.textContent = '0%';
            if (igstSpan) igstSpan.textContent = '0%';
        });

        calculateTotals();
    }


    // add item
    // Show modal and fetch order items
    document.getElementById('addInvoiceItemBtn').addEventListener('click', function() {
        document.getElementById('orderModal').style.display = 'flex';
        document.getElementById('orderSearch').value = '';
        fetchOrderItems('');
    });

    // Close modal
    document.getElementById('closeOrderModal').onclick = function() {
        document.getElementById('orderModal').style.display = 'none';
    };

    // Search filter (fetches filtered items)
    document.getElementById('orderSearch').addEventListener('input', function() {
        if (this.value.length < 3 && this.value.length > 0) return; // Minimum 3 characters to search
        fetchOrderItems(this.value);
    });

    function fetchOrderItems(searchTerm) {
        fetch('<?php echo base_url('?page=invoices&action=fetch_items'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    search: searchTerm,
                    customer_id: <?php echo isset($customer['id']) ? (int)$customer['id'] : 0; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('orderItemsTableBody');
                tbody.innerHTML = '';

                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `                    
                    <td class="border-b border-gray-100 p-3" data-item='${JSON.stringify(item)}'>${invoiceOrderDetailsLink(item.order_number) || (item.order_number || '')}</td>
                    <td class="border-b border-gray-100 p-3">${item.sku || ''}</td>
                    <td class="border-b border-gray-100 p-3">${item.title || ''}</td>
                    <td class="border-b border-gray-100 p-3 text-right">${item.unit_price ? "₹"+item.unit_price : '0.00'}</td>
                    <td class="border-b border-gray-100 p-3 text-center">${item.quantity || 0}</td>
                    <td class="border-b border-gray-100 p-3 text-center">
                        <button type="button" class="select-item-button rounded-lg bg-orange-500 px-3 py-1 text-sm font-semibold text-white hover:bg-orange-600" id="selectItemBtn">Select</button>
                    </td>
                `;
                        tbody.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td class="p-4 text-center text-gray-500" colspan="6">No items found</td>`;
                    tbody.appendChild(row);
                }
            })
            .catch(err => {
                console.error('Error fetching order items:', err);
            });
    }
    // Handle item selection
    document.getElementById('orderItemsTableBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('select-item-button')) {
            const itemData = JSON.parse(e.target.closest('tr').querySelector('td').getAttribute('data-item'));

            // Check if item already exists in invoice table
            const existingRows = document.querySelectorAll('#invoiceTable tbody tr');
            let itemExists = false;

            existingRows.forEach(row => {
                const existingItemCode = row.querySelector('input[name="item_code[]"]')?.value;
                const existingOrderNumber = row.querySelector('input[name="order_number[]"]')?.value;

                if (existingItemCode === itemData.item_code && existingOrderNumber === itemData.order_number) {
                    itemExists = true;
                    showAlert('Item already added to invoice', 'warning');
                    // Increase quantity if item already exists
                    // const quantityInput = row.querySelector('input[name="quantity[]"]');
                    // const quantitySpan = quantityInput.parentElement.querySelector('span');
                    // const currentQty = parseFloat(quantityInput.value) || 1;
                    // const newQty = currentQty + (parseFloat(itemData.quantity) || 1);
                    // quantityInput.value = newQty;
                    // if (quantitySpan) {
                    //     quantitySpan.textContent = newQty;
                    // }
                }
            });

            // Only add new row if item doesn't exist
            if (!itemExists) {
                const tbody = document.querySelector('#invoiceTable tbody');
                const newRow = document.createElement('tr');
                newRow.className = 'bg-gray-50';
                const addPrefix = (itemData.currency || 'INR') === 'INR' ? '₹' : (itemData.currency || 'INR') + ' ';
                const addImage = itemData.image ? `<img src="${htmlspecialchars(itemData.image)}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200">` : '';
                newRow.innerHTML = `
                <td class="rounded-l-xl px-3 py-3 font-medium text-gray-500">
                    <input type="hidden" name="order_number[]" value="${itemData.order_number || ''}">
                    <input type="hidden" name="item_code[]" value="${itemData.item_code || ''}">
                    <input type="hidden" name="gst[]" value="${itemData.gst || '0'}">
                    <input type="hidden" name="tax_rate[]" value="${itemData.gst || '0'}">
                    <input type="hidden" name="currency[]" value="${itemData.currency || 'INR'}">
                    <input type="hidden" name="image_url[]" value="${itemData.image || ''}">
                    <input type="hidden" name="groupname[]" value="${itemData.groupname || ''}">
                    ${tbody.children.length + 1}
                </td>
                <td class="px-3 py-3 font-medium text-slate-800"><span>${itemData.sku || ''}</span>${itemData.order_number ? `<div class="mt-1">${invoiceOrderDetailsLink(itemData.order_number)}</div>` : ''}</td>
                <td class="px-3 py-3" colspan="2">
                    <div class="flex items-start gap-3">${addImage}<span class="leading-snug">${itemData.title ? htmlspecialchars(itemData.title) : ''}</span></div>
                    <input type="hidden" name="item_name[]" value="${itemData.title ? htmlspecialchars(itemData.title) : ''}" required>
                </td>
                <td class="p-2">
                    <input type="text" name="box_no[]" class="w-16 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-sm" value="1" required>
                </td>
                <td class="px-3 py-3 text-gray-600"><span>${itemData.hsn || ''}</span>
                    <input type="hidden" name="hsn[]" value="${itemData.hsn || ''}">
                </td>
                <td class="px-3 py-3 text-right"><span>${itemData.quantity || 0}</span>
                    <input type="hidden" name="quantity[]" value="${itemData.quantity || 0}">
                </td>
                <td class="px-3 py-3 text-right tabular-nums"><span>${itemData.unit_price ? addPrefix + itemData.unit_price : '0.00'}</span>
                    <input type="hidden" name="unit_price[]" value="${itemData.unit_price || 0}">
                </td>
                <td class="px-3 py-3 text-right text-gray-500"><span>0%</span>
                    <input type="hidden" name="discount[]" value="0">
                </td>
                <td class="px-3 py-3 text-right"><span>0%</span>
                    <input type="hidden" name="cgst[]" value="0">
                </td>
                <td class="px-3 py-3 text-right"><span>0%</span>
                    <input type="hidden" name="sgst[]" value="0">
                </td>
                <td class="px-3 py-3 text-right"><span>0%</span>
                    <input type="hidden" name="igst[]" value="0">
                </td>
                <td class="px-3 py-3 text-right font-semibold tabular-nums"><span>${itemData.unit_price ? addPrefix + itemData.unit_price : '0.00'}</span>
                    <input type="hidden" name="line_total[]" step="0.01">
                </td>
                <td class="rounded-r-xl px-3 py-3 text-center">
                    <button type="button" onclick="removeRow(this)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700" title="Remove item">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
                tbody.appendChild(newRow);
            }

            // Update GST fields based on current billing state
            refreshCurrentInvoiceGst();

            calculateTotals();
            // Close modal
            document.getElementById('orderModal').style.display = 'none';
        }
    });

    function createAndDispatch() {
        // submit create_invoice and redirect to dispatch page with invoice_id
        const form = document.getElementById('create_invoice');
        const formData = new FormData(form);
        formData.append('dispatch_after_creation', '1'); // Add a flag to indicate dispatch after creation

        const submitBtn2 = document.getElementById('createAndDispatchButton');
        submitBtn2.disabled = true;
        submitBtn2.innerHTML = '<span class="animate-spin">⏳</span> Processing...';
        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    response.json().then(data => {
                        if (data.invoice_id) {
                            window.location.href = '<?php echo base_url('?page=dispatch&action=create&invoice_id='); ?>' + data.invoice_id;
                        } else {
                            invoiceNotify('Failed to create invoice and dispatch', 'error');
                        }
                    });
                } else {
                    invoiceNotify('Failed to create invoice and dispatch', 'error');
                }
                submitBtn2.disabled = false;
                submitBtn2.innerHTML = 'Create & Dispatch';
            })
            .catch(error => {
                console.error('Error:', error);
                invoiceNotify('An error occurred while creating invoice and dispatching', 'error');
            });

    }

    // Function to regenerate IRN
    function regenerateIrn(invoiceId) {
        const regenerateBtn = document.querySelector('button[onclick*="regenerateIrn"]');
        if (regenerateBtn) {
            regenerateBtn.disabled = true;
            regenerateBtn.innerHTML = 'Regenerating...';
        }

        const payload = {
            invoice_id: invoiceId,
            pre_carriage_by: document.getElementById('pre_carriage_by')?.value.trim() || '',
            port_of_loading: document.getElementById('port_of_loading')?.value.trim() || '',
            port_of_discharge: document.getElementById('port_of_discharge')?.value.trim() || '',
            country_of_origin: document.getElementById('country_of_origin')?.value.trim() || '',
            country_of_final_destination: document.getElementById('country_of_final_destination')?.value.trim() || '',
            final_destination: document.getElementById('final_destination')?.value.trim() || '',
            usd_export_rate: document.getElementById('usd_export_rate')?.value.trim() || '',
            ap_cost: document.getElementById('ap_cost')?.value.trim() || '',
            freight_charge: document.getElementById('freight_charge')?.value.trim() || '',
            insurance_charge: document.getElementById('insurance_charge')?.value.trim() || '',
            shipping_bill_number: document.getElementById('shipping_bill_number')?.value.trim() || '',
            shipping_bill_date: document.getElementById('shipping_bill_date')?.value.trim() || '',
            shipping_port: document.getElementById('shipping_port')?.value.trim() || '',
            shipping_ref_clm: document.getElementById('shipping_ref_clm')?.value.trim() || '',
            shipping_currency: document.getElementById('shipping_currency')?.value.trim() || '',
            shipping_country_code: document.getElementById('shipping_country_code')?.value.trim() || '',
            shipping_exp_duty: document.getElementById('shipping_exp_duty')?.value.trim() || ''
        };

        fetch('<?php echo base_url('?page=invoices&action=regenerate_irn'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('IRN regenerated successfully!', 'success');
                    // Remove the regenerate button
                    if (regenerateBtn) {
                        regenerateBtn.remove();
                    }
                    // Proceed with normal flow (PDF generation and redirect)
                    setTimeout(() => {
                        fetch('<?php echo base_url('?page=invoices&action=generate_pdf'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    invoice_id: invoiceId
                                })
                            })
                            .then(response => response.blob())
                            .then(blob => {
                                const url = window.URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = 'invoice_' + invoiceId + '.pdf';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                window.URL.revokeObjectURL(url);
                                if (data.is_international) {
                                    window.location.href = '<?php echo base_url('?page=dispatch&action=create&invoice_id='); ?>' + invoiceId;
                                    return;
                                }

                                // Redirect to orders list
                                setTimeout(() => {
                                    window.location.href = '<?php echo base_url('?page=orders&action=list'); ?>';                                    
                                }, 1000);
                            })
                            .catch(err => {
                                console.error('PDF generation error:', err);
                                window.location.href = '<?php echo base_url('?page=orders&action=list'); ?>';
                            });
                    }, 1000);
                } else {
                    showAlert('IRN regeneration failed: ' + data.message, 'error');
                    if (regenerateBtn) {
                        regenerateBtn.disabled = false;
                        regenerateBtn.innerHTML = 'Regenerate IRN';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error occurred during IRN regeneration', 'error');
                if (regenerateBtn) {
                    regenerateBtn.disabled = false;
                    regenerateBtn.innerHTML = 'Regenerate IRN';
                }
            });
    }
</script>