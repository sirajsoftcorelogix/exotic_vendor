<style>
    .scrollbar-visible::-webkit-scrollbar {
        height: 6px;
    }

    .scrollbar-visible::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .scrollbar-visible::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 10px;
    }

    .scrollbar-visible::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }
</style>
<?php
$total_price = 0;
$currency = '';

foreach ($order as $items => $item):
    $total_price += $item['finalprice'] * $item['quantity'];
endforeach;
$currencyIcons = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥'];
$orderremarks = is_array($orderremarks ?? null) ? $orderremarks : [];
$customerdetails = is_array($customerdetails ?? null) ? $customerdetails : [];
$statusList = is_array($statusList ?? null) ? $statusList : [];
$order_status_list = is_array($order_status_list ?? null) ? $order_status_list : [];
$staff_list = is_array($staff_list ?? null) ? $staff_list : [];
$showOrderVendorName = (bool)($showOrderVendorName ?? false);
$countries = country_array();
$buildStatusOrderPayload = static function (array $item): array {
    return [
        'order_id' => (int)($item['id'] ?? 0),
        'order_number' => (string)($item['order_number'] ?? ''),
        'item_code' => (string)($item['item_code'] ?? ''),
        'vendor_name' => (string)($item['vendor_name'] ?? $item['vendor'] ?? ''),
        'groupname' => (string)($item['groupname'] ?? ''),
        'subcategories' => (string)($item['subcategories'] ?? ''),
        'title' => (string)($item['title'] ?? ''),
        'image' => (string)($item['image'] ?? ''),
        'status' => (string)($item['status'] ?? ''),
        'priority' => (string)($item['priority'] ?? ''),
        'agent_id' => (string)($item['agent_id'] ?? ''),
        'esd' => (string)($item['esd'] ?? ''),
        'remarks' => (string)($item['remarks'] ?? ''),
    ];
};
$displayOrderNumber = (string)($orderremarks['order_number'] ?? ($order[0]['order_number'] ?? ''));
$resolveCountryLabel = static function (?string $code) use ($countries): string {
    $code = trim((string)$code);
    if ($code === '') {
        return '';
    }
    return (string)($countries[$code] ?? $code);
};
$salesReturnUrl = base_url('?page=sales_returns&action=create&order_number=' . rawurlencode($displayOrderNumber));
$invoiceIdForReturn = (int)($order[0]['invoice_id'] ?? 0);
if ($invoiceIdForReturn > 0) {
    $salesReturnUrl .= '&invoice_id=' . $invoiceIdForReturn;
}
?>

<div class="min-h-screen bg-gray-50 p-6 font-sans text-black-900">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold"><?php echo htmlspecialchars((string)($orderremarks['order_number'] ?? '')); ?></h1>
            <!-- <span class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white">Paid</span>
            <span class="rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-white">Canceled</span>
            <span class="rounded-full bg-yellow-500 px-3 py-1 text-xs font-semibold text-white">Refunded</span>
            <span class="rounded-full bg-gray-400 px-3 py-1 text-xs font-semibold text-white">Unfulfilled</span>
            <span class="rounded-full bg-orange-600 px-3 py-1 text-xs font-semibold text-white">Fulfilled</span>
            <span class="rounded-full bg-black px-3 py-1 text-xs font-semibold text-white">Archived</span> -->
        </div>

        <div class="flex items-center gap-2">
            <button class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">Restock</button>
            <button type="button"
                data-sales-return-create
                data-sales-return-url="<?= htmlspecialchars($salesReturnUrl, ENT_QUOTES, 'UTF-8') ?>"
                data-order-number="<?= htmlspecialchars($displayOrderNumber, ENT_QUOTES, 'UTF-8') ?>"
                class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">
                Return
            </button>
            <button class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">Edit</button>
            <div class="relative inline-block text-left">
                <input type="checkbox" id="dropdown-toggle" class="peer hidden">
                <label for="dropdown-toggle" class="flex cursor-pointer items-center gap-2 rounded bg-black px-4 py-1.5 text-sm font-medium text-white hover:bg-gray-800 transition-colors select-none">
                    Print
                    <svg class="w-4 h-4 transition-transform duration-200 peer-checked:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </label>
                <div class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden opacity-0 invisible scale-95 transition-all duration-200 peer-checked:opacity-100 peer-checked:visible peer-checked:scale-100">
                    <div class="py-1">
                        <a href="#" class="flex items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100">
                            Print Invoice
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100 border-t border-gray-50">
                            print order
                        </a>
                    </div>
                </div>
                <label for="dropdown-toggle" class="fixed inset-0 h-full w-full cursor-default hidden peer-checked:block z-40"></label>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4 font-sans text-[#333]">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6 space-y-3">
                    <div class="flex items-center gap-2">
                        <?php /*<div
                            class="flex items-center gap-2 rounded bg-[#E5E7EB] px-3 py-1 text-xs font-medium text-black-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Fulfilled (32)</span>
                        </div> */ ?>
                        <?php
                        $city = $orderremarks['city'] ?? '';
                        $state = $orderremarks['state'] ?? '';

                        $location = implode(', ', array_filter([$city, $state]));
                        ?>
                        <?php if (!empty($location)) : ?>
                            <div class="flex items-center gap-2 rounded bg-[#E5E7EB] px-3 py-1 text-xs font-medium text-black-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="1.5">
                                    <path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path
                                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                <span><?php echo $location; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                        <svg class="h-5 w-5 text-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span
                            class="text-sm font-medium text-black-600"><?php echo date('d-M-Y', strtotime($orderremarks['created_at'] ?? '')); ?></span>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php foreach ($order as $item):
                        $currencyCode = strtoupper(trim($item['currency'] ?? ''));
                        if (isset($currencyIcons[$currencyCode]) && $currencyIcons[$currencyCode] !== '') {
                            $currencysymbol = $currencyIcons[$currencyCode] ?? $currencyCode;
                        } else {
                            $currencysymbol = $currencyCode . ' ';
                        }
                        $lineId = (int)($item['id'] ?? 0);
                        $lineStatus = (string)($item['status'] ?? '');
                        $lineStatusLabel = (string)($statusList[$lineStatus] ?? ucwords(str_replace('_', ' ', $lineStatus)));
                        $lineAgentId = (int)($item['agent_id'] ?? 0);
                        $lineAgentName = $lineAgentId > 0 ? (string)($staff_list[$lineAgentId] ?? 'N/A') : 'N/A';
                        $linePriority = trim((string)($item['priority'] ?? ''));
                        $lineEsd = trim((string)($item['esd'] ?? ''));
                        $statusOrderPayload = $buildStatusOrderPayload($item);
                    ?>
                        <div class="flex items-center gap-4 accordion-trigger">
                            <input type="checkbox" class="h-5 w-5 rounded border-gray-300">
                            <div class="flex flex-1 items-start gap-5 rounded-2xl border border-gray-200 p-4">
                                <div class="h-32 w-32 flex-shrink-0 overflow-hidden rounded-xl border border-gray-100">
                                    <img src="<?php echo $item['image']; ?>" class="h-full w-full object-cover"
                                        alt="product">
                                </div>

                                <div class="flex-1">
                                    <!-- <h4 class="mb-3 text-[12px] font-semibold leading-tight text-black-900">
                                    <?php echo $item['groupname']; ?> / <?php echo $item['subcategories']; ?>
                                </h4> -->
                                    <h4 class="mb-3 text-[14px] leading-tight text-black-900">
                                        <?php echo $item['title']; ?>
                                    </h4>

                                    <div class="flex justify-between items-start">
                                        <div class="space-y-1.5 text-[13px]">
                                            <p>
                                                <span class="inline-block w-12 font-bold text-black">SKU</span>
                                                <span class="text-black">:</span>
                                                <span class="ml-2 text-black-700"><?php echo $item['sku']; ?></span>
                                            </p>
                                            <p>
                                                <span class="inline-block w-12 font-bold text-black">Color</span>
                                                <span class="text-black">:</span>
                                                <span class="ml-2 text-black-700"><?php echo $item['color']; ?></span>
                                            </p>
                                            <div class="flex items-center pt-1">
                                                <span class="inline-block w-12 font-bold text-black">Qty.</span>
                                                <span class="text-black">:</span>
                                                <span
                                                    class="ml-4 rounded-full border border-gray-200 bg-gray-50 px-5 py-0.5 text-black-800">
                                                    <?php echo str_pad($item['quantity'], 2, '0', STR_PAD_LEFT); ?>
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-1 gap-1 pt-2 text-[12px] text-black-600">
                                                <p><span class="font-bold text-black">Priority</span>: <?php echo $linePriority !== '' ? htmlspecialchars(ucfirst($linePriority)) : '—'; ?></p>
                                                <p><span class="font-bold text-black">Agent</span>: <?php echo htmlspecialchars($lineAgentName); ?></p>
                                                <p><span class="font-bold text-black">Ship by</span>: <?php echo $lineEsd !== '' ? htmlspecialchars(date('d M Y', strtotime($lineEsd))) : '—'; ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <div class="flex items-center gap-2 text-[13px] text-black-500">
                                                <span><?php echo $currencysymbol; ?><?php echo $item['finalprice']; ?> x</span>
                                                <span class="rounded bg-gray-100 px-2 py-0.5 text-black-700"><?php echo $item['quantity']; ?></span>
                                            </div>

                                            <div class="w-20 text-right text-[14px] font-bold text-black-900">
                                                <?php echo $currencysymbol; ?><?php echo $item['finalprice'] * $item['quantity']; ?>
                                            </div>
                                            <div class="flex-shrink-0 flex flex-col items-end gap-2">
                                                <span class="rounded-full bg-green-600 px-3 py-1 text-[11px] font-semibold text-white whitespace-nowrap"><?php echo htmlspecialchars($lineStatusLabel); ?></span>
                                                <button type="button"
                                                    onclick="openStatusPopup(<?= $lineId ?>)"
                                                    class="text-[11px] font-semibold text-orange-700 hover:text-orange-900 hover:underline">
                                                    Update status
                                                </button>
                                                <span id="order-id-<?= $lineId ?>" class="hidden" data-order='<?= htmlspecialchars(json_encode($statusOrderPayload), ENT_QUOTES, 'UTF-8') ?>'></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-content-details max-h-0 overflow-hidden transition-all duration-300 ease-in-out [&:has(>input:checked)]:max-h-[1200px] bg-gray-50">
                            <div class="bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                                <p class="flex flex-wrap items-center gap-2">
                                    <span class="section-title font-bold text-gray-700 text-sm italic">Addons : </span>
                                    <span class="section-value text-green-700 font-semibold text-sm bg-green-50 px-2.5 py-1 rounded-lg border border-green-100">
                                        <?php
                                        $options = json_decode($item['options'], true);
                                        echo !empty($options) ? implode(', ', $options) : 'None';
                                        ?>
                                    </span>
                                </p>
                            </div>
                            <div class="py-6 bg-white border-t border-b border-gray-100">
                                <div class="overflow-x-auto pb-4 px-4">
                                    <div class="relative flex items-start min-w-max">
                                        <div class="relative z-10 flex flex-col items-center w-[120px]">
                                            <div class="w-4 h-4 rounded-full bg-[#27AE60] border-[3px] border-white z-20"></div>

                                            <?php if (!empty($item['status_log'])): ?>
                                                <div class="absolute top-[8px] left-1/2 w-full h-[2px] bg-[#27AE60] z-0"></div>
                                            <?php endif; ?>

                                            <div class="mt-4 text-center px-2">
                                                <p class="text-[12px] font-bold text-gray-900 leading-tight">Created</p>
                                                <p class="text-[10px] text-gray-500 mt-1"><?= date('d M, Y', strtotime($item['order_date'] ?? 'now')) ?></p>
                                                <p class="text-[9px] text-gray-400 italic">System</p>
                                            </div>
                                        </div>

                                        <?php if (!empty($item['status_log'])):
                                            $totalSteps = count($item['status_log']);
                                            foreach ($item['status_log'] as $index => $log):
                                                $isLast = ($index === $totalSteps - 1);
                                        ?>
                                                <div class="relative z-10 flex flex-col items-center w-[120px]">
                                                    <div class="w-4 h-4 rounded-full bg-[#27AE60] border-[3px] border-white z-20"></div>
                                                    <?php if (!$isLast): ?>
                                                        <div class="absolute top-[8px] left-1/2 w-full h-[2px] bg-[#27AE60] z-0"></div>
                                                    <?php endif; ?>

                                                    <div class="mt-4 text-center px-2">
                                                        <p class="text-[11px] font-bold text-gray-900 leading-tight">
                                                            Agent: <?= htmlspecialchars($log['changed_by_username']) ?>
                                                        </p>
                                                        <p class="text-[10px] text-gray-500 mt-0.5"><?= date('d M, Y', strtotime($log['change_date'])) ?></p>
                                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter mt-1">
                                                            <?= str_replace('_', ' ', $log['status']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                        <?php endforeach;
                                        endif; ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                /*
                        $tax_rate = 0.05;
                        $coupon_reduce      = floatval($orderremarks['coupon_reduce']      ?? 0);
                        $giftvoucher_reduce = floatval($orderremarks['giftvoucher_reduce'] ?? 0);
                        $credit             = floatval($orderremarks['credit']             ?? 0);
                        $all_reductions = $coupon_reduce + $giftvoucher_reduce + $credit;
                        $final_paid = floatval($orderremarks['total'] ?? 0);
                        $amount_before_tax = $final_paid / (1 + $tax_rate);
                        $tax_amount = $final_paid - $amount_before_tax;
                        $subtotal_before_discounts = $amount_before_tax + $all_reductions;
                    */
                $custom_reduce      = floatval($orderremarks['custom_reduce']      ?? 0);
                $coupon_reduce      = floatval($orderremarks['coupon_reduce']      ?? 0);
                $giftvoucher_reduce = floatval($orderremarks['giftvoucher_reduce'] ?? 0);
                $credit             = floatval($orderremarks['credit']             ?? 0);
                $all_reductions = $custom_reduce + $coupon_reduce + $giftvoucher_reduce + $credit;
                $final_paid = floatval($orderremarks['total'] ?? 0);
                $tax_amount = 0.0;
                foreach ($order as $item) {
                    $qty        = (int)($item['quantity'] ?? 1);
                    $unit_price = floatval($item['finalprice'] ?? 0);   // ← Pre-GST unit price
                    $gst_percent = floatval($item['gst'] ?? 0);         // ← GST percentage from DB
                    $line_total_excl_gst = $unit_price * $qty;
                    $line_gst_amount     = $line_total_excl_gst * ($gst_percent / 100);
                    $tax_amount += $line_gst_amount;
                }
                $tax_amount = round($tax_amount, 2);   // clean money value
                // Derive remaining values (keeps everything 100% consistent with final_paid)
                $amount_before_tax       = $final_paid - $tax_amount;
                $subtotal_before_discounts = $amount_before_tax + $all_reductions;
                ?>
                <div class="mt-6 bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                    <!-- <div class="mb-5">
                            <span class="inline-flex items-center gap-2 bg-[#E5E7EB] text-[#5C5F62] px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-medium">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-[#5C5F62]">
                                    <path d="M19 3H5C3.89543 3 3 3.89543 3 5V21L5.5 18.5L8 21L10.5 18.5L13 21L15.5 18.5L18 21L21 18V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 11L11 13L15 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Paid
                            </span>
                        </div> -->

                    <div class="border border-gray-200 rounded-xl overflow-hidden">

                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-12 items-start text-sm">
                                <div class="col-span-3 font-bold text-black-800">Subtotal</div>
                                <div class="col-span-6 text-black-500"><?php echo count($order); ?> items</div>
                                <div class="col-span-3 text-right font-bold text-black-900">
                                    <?php echo $currencysymbol; ?><?php echo number_format($subtotal_before_discounts, 2); ?>
                                </div>
                            </div>
                            <!-- Individual discount rows -->
                            <?php if ($all_reductions > 0): ?>
                                <?php if ($coupon_reduce > 0 && !empty($orderremarks['coupon'])): ?>
                                    <div class="grid grid-cols-12 items-start text-sm text-green-700">
                                        <div class="col-span-3 font-medium">Coupon </div>
                                        <div class="col-span-6 text-gray-600">
                                            <?php echo htmlspecialchars($orderremarks['coupon']); ?></div>
                                        <div class="col-span-3 text-right font-medium">
                                            -<?php echo $currencysymbol; ?><?php echo number_format($coupon_reduce, 2); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($giftvoucher_reduce > 0 && !empty($orderremarks['giftvoucher'])): ?>
                                    <div class="grid grid-cols-12 items-start text-sm text-green-700">
                                        <div class="col-span-3 font-medium">Gift Voucher </div>
                                        <div class="col-span-6 text-gray-600">
                                            <?php echo htmlspecialchars($orderremarks['giftvoucher']); ?></div>
                                        <div class="col-span-3 text-right font-medium">
                                            -<?php echo $currencysymbol; ?><?php echo number_format($giftvoucher_reduce, 2); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($custom_reduce > 0 && !empty($orderremarks['custom_reduce'])): ?>
                                    <div class="grid grid-cols-12 items-start text-sm text-green-700">
                                        <div class="col-span-3 font-medium">Custom Reduce </div>
                                        <div class="col-span-6 text-gray-600">
                                            <?php echo htmlspecialchars($orderremarks['custom_reduce']); ?></div>
                                        <div class="col-span-3 text-right font-medium">
                                            -<?php echo $currencysymbol; ?><?php echo number_format($custom_reduce, 2); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($credit > 0): ?>
                                    <div class="grid grid-cols-12 items-start text-sm text-green-700">
                                        <div class="col-span-3 font-medium">Credit / Wallet</div>
                                        <div class="col-span-6 text-gray-600"></div>
                                        <div class="col-span-3 text-right font-medium">
                                            -<?php echo $currencysymbol; ?><?php echo number_format($credit, 2); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <!-- Taxes -->
                            <div class="grid grid-cols-12 items-start text-sm">
                                <div class="col-span-3 font-bold text-black-800">Taxes</div>
                                <div class="col-span-6 text-black-500">SGST + CGST</div>
                                <div class="col-span-3 text-right font-bold text-black-900">
                                    <?php echo $currencysymbol; ?><?php echo number_format($tax_amount, 2); ?>
                                </div>
                            </div>
                            <!-- Final Total -->
                            <div class="grid grid-cols-12 items-start text-sm pt-1 border-t border-gray-200 pt-3">
                                <div class="col-span-3 font-bold text-black-800">Total</div>
                                <div class="col-span-6"></div>
                                <div class="col-span-3 text-right font-bold text-black-900 text-lg">
                                    <?php echo $currencysymbol; ?><?php echo number_format($final_paid, 2); ?>
                                </div>
                            </div>
                        </div>
                        <div class="bg-[#F9FAFB] border-t border-gray-200 p-6 flex justify-between items-center">
                            <span class="text-sm font-bold text-black-800">Paid</span>
                            <span class="text-sm font-bold text-black-900">
                                <?php echo $currencysymbol; ?><?php echo number_format($final_paid, 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($fullOrderJourny)) { ?>
                    <div class="space-y-4 mt-8">
                        <div class="py-6 bg-[#F9FAFB] border border-gray-100 rounded-xl">
                            <h5 class="text-[10px] font-bold uppercase tracking-widest text-[#8E959F] mb-8 px-8">ORDER JOURNEY</h5>

                            <div class="relative flex flex-col px-8 space-y-0">
                                <?php
                                $totalItems = count($fullOrderJourny);
                                $currentIteration = 0;

                                foreach ($fullOrderJourny as $journey) {
                                    $currentIteration++;
                                    $isLast = ($currentIteration === $totalItems);
                                ?>
                                    <div class="relative flex gap-x-4 pb-8">
                                        <?php if (!$isLast): ?>
                                            <div class="absolute top-2 left-[7px] w-[2px] h-full bg-[#27AE60] z-0"></div>
                                        <?php endif; ?>

                                        <div
                                            class="relative z-10 w-4 h-4 rounded-full bg-[#27AE60] border-[3px] border-white shadow-sm flex-shrink-0">
                                        </div>

                                        <div class="flex flex-col">
                                            <p class="text-[12px] font-bold text-gray-900 leading-none">
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $journey['status']))); ?>
                                            </p>
                                            <p class="text-[10px] text-gray-500 mt-1">
                                                <span class="font-medium text-gray-700">By:</span>
                                                <?php echo htmlspecialchars($journey['changed_by']); ?>
                                            </p>
                                            <p class="text-[9px] text-[#8E959F] italic mt-0.5">
                                                <?php echo date('d M, Y | h:i A', strtotime($journey['created_on'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm relative mt-8" id="order-address-section">
                    <button type="button"
                        onclick="openNameEmailPopup('<?= htmlspecialchars($orderremarks['order_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>')"
                        class="absolute top-4 right-4 text-black-500 hover:text-blue-600 transition-colors"
                        title="Edit addresses">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                    <h3 class="mb-4 text-sm font-bold text-black-700">Shipping &amp; Billing Address</h3>
                    <span id="display-customer-name" class="hidden"><?php echo htmlspecialchars($customerdetails['customer_name'] ?? ''); ?></span>
                    <span id="display-customer-phone" class="hidden"><?php echo htmlspecialchars($customerdetails['customer_phone'] ?? ''); ?></span>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-black-400">Shipping address</h4>
                            <address class="mt-2 text-sm not-italic text-black-800 leading-relaxed">
                                <?php if (!empty($customerdetails['customer_name'])): ?>
                                    <span class="block font-medium"><?php echo htmlspecialchars($customerdetails['customer_name']); ?></span>
                                <?php endif; ?>
                                <span id="shipping_address1"><?php echo htmlspecialchars($orderremarks['shipping_address_line1'] ?? ''); ?></span>
                                <?php if (!empty($orderremarks['shipping_address_line2'])): ?>
                                    <br><span id="shipping_address2"><?php echo htmlspecialchars($orderremarks['shipping_address_line2']); ?></span>
                                <?php else: ?>
                                    <span id="shipping_address2" class="hidden"></span>
                                <?php endif; ?>
                                <br>
                                <span id="shipping_city"><?php echo htmlspecialchars($orderremarks['shipping_city'] ?? ''); ?></span><?php if (!empty($orderremarks['shipping_state'])): ?>,
                                    <span id="shipping_state"><?php echo htmlspecialchars($orderremarks['shipping_state']); ?></span><?php else: ?><span id="shipping_state" class="hidden"></span><?php endif; ?>
                                <?php if (!empty($orderremarks['shipping_zipcode'])): ?>
                                    - <span id="shipping_zipcode"><?php echo htmlspecialchars($orderremarks['shipping_zipcode']); ?></span>
                                <?php else: ?>
                                    <span id="shipping_zipcode" class="hidden"></span>
                                <?php endif; ?>
                                <?php if (!empty($orderremarks['shipping_country'])): ?>
                                    <br><span id="shipping_country" data-code="<?php echo htmlspecialchars($orderremarks['shipping_country']); ?>"><?php echo htmlspecialchars($resolveCountryLabel($orderremarks['shipping_country'])); ?></span>
                                <?php else: ?>
                                    <span id="shipping_country" class="hidden"></span>
                                <?php endif; ?>
                                <?php if (!empty($orderremarks['shipping_mobile'])): ?>
                                    <br><span id="shipping_mobile" class="mt-1 block"><?php echo htmlspecialchars($orderremarks['shipping_mobile']); ?></span>
                                <?php else: ?>
                                    <span id="shipping_mobile" class="hidden"></span>
                                <?php endif; ?>
                                <?php if (!empty($orderremarks['shipping_gstin'])): ?>
                                    <br><span class="text-xs text-gray-500">GSTIN:</span> <span id="shipping_gstin"><?php echo htmlspecialchars($orderremarks['shipping_gstin']); ?></span>
                                <?php else: ?>
                                    <span id="shipping_gstin" class="hidden"></span>
                                <?php endif; ?>
                            </address>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-black-400">Billing address</h4>
                            <address class="mt-2 text-sm not-italic text-black-800 leading-relaxed">
                                <span id="billing_address1"><?php echo htmlspecialchars($orderremarks['address_line1'] ?? ''); ?></span>
                                <?php if (!empty($orderremarks['address_line2'])): ?>
                                    <br><span id="billing_address2"><?php echo htmlspecialchars($orderremarks['address_line2']); ?></span>
                                <?php else: ?>
                                    <span id="billing_address2" class="hidden"></span>
                                <?php endif; ?>
                                <br>
                                <span id="billing_city"><?php echo htmlspecialchars($orderremarks['city'] ?? ''); ?></span><?php if (!empty($orderremarks['state'])): ?>,
                                    <span id="billing_state"><?php echo htmlspecialchars($orderremarks['state']); ?></span><?php else: ?><span id="billing_state" class="hidden"></span><?php endif; ?>
                                <?php if (!empty($orderremarks['zipcode'])): ?>
                                    - <span id="billing_zipcode"><?php echo htmlspecialchars($orderremarks['zipcode']); ?></span>
                                <?php else: ?>
                                    <span id="billing_zipcode" class="hidden"></span>
                                <?php endif; ?>
                                <?php if (!empty($orderremarks['country'])): ?>
                                    <br><span id="billing_country" data-code="<?php echo htmlspecialchars($orderremarks['country']); ?>"><?php echo htmlspecialchars($resolveCountryLabel($orderremarks['country'])); ?></span>
                                <?php else: ?>
                                    <span id="billing_country" class="hidden"></span>
                                <?php endif; ?>
                                <?php if (!empty($orderremarks['gstin'])): ?>
                                    <br><span class="text-xs text-gray-500">GSTIN:</span> <span id="billing_gstin"><?php echo htmlspecialchars($orderremarks['gstin']); ?></span>
                                <?php else: ?>
                                    <span id="billing_gstin" class="hidden"></span>
                                <?php endif; ?>
                            </address>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="space-y-6">
            <!-- Note Section -->
            <div class="rounded-lg border bg-white p-5 shadow-sm relative" id="note-container-<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>">
                <button type="button" onclick="openNoteEditPopup('<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>','<?= htmlspecialchars($orderremarks['remarks'] ?? '', ENT_QUOTES) ?>')" class="absolute top-4 right-4 text-black-500 hover:text-blue-600 transition-colors" title="Edit Note">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <h3 class="mb-2 text-sm font-bold text-black-700">Note</h3>
                <?php if (!empty($orderremarks['remarks'])): ?>
                    <div id="note-display-<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>" class="text-sm text-black-700 max-h-[180px] overflow-y-auto break-words leading-relaxed bg-gray-50 p-3 rounded-md border border-gray-200">
                        <?php echo ($orderremarks['remarks']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Conversion Summary -->
            <?php if (!empty($orderremarks['payment_type']) || !empty($orderremarks['country'])): ?>
                <div class="rounded-lg border bg-white p-5 shadow-sm relative">
                    <h3 class="mb-2 text-sm font-bold text-black-700">Conversion Summary</h3>
                    <div
                        class="text-sm text-black-700 max-h-[180px] overflow-y-auto break-words leading-relaxed bg-gray-50 p-3 rounded-md border border-gray-200">
                        <b>Payment Type:</b> <?php echo ($orderremarks['payment_type'] ?? 'N/A'); ?>
                        <br>
                        <b>Payment ID:</b> <?php echo ($orderremarks['transid'] ?? 'N/A'); ?>
                        <br>
                        <b>Country:</b> <?php echo ($orderremarks['country'] ?? 'N/A'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div id="noteEditPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 p-6 relative">
        <button onclick="closeNotePopup()" class="absolute top-3 right-4 text-black-500 hover:text-black-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-xl font-bold mb-4 text-black-800">Edit Customer Note</h2>

        <form id="noteEditForm">
            <input type="hidden" id="note_order_number" name="order_number">

            <textarea id="note_remarks" name="remarks" rows="6"
                class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y"
                placeholder="Enter note / remarks here..."></textarea>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeNotePopup()" class="rounded-full px-5 py-2.5 bg-gray-200 text-black-800 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="rounded-full bg-[#D46B08] px-10 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-700">
                    Save Note
                </button>
            </div>
        </form>
    </div>
</div>
<div id="nameEmailPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-auto flex flex-col max-h-[90vh] relative">

        <div class="p-6 pb-0">
            <button onclick="closeNameEmailPopup()" class="absolute top-3 right-4 text-gray-500 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <h2 class="text-lg font-bold mb-4 text-gray-800">Edit Customer &amp; Addresses</h2>
        </div>

        <div class="overflow-y-auto p-6 pt-2 custom-scrollbar">
            <form id="nameEmailForm">
                <input type="hidden" id="edit_order_number" name="order_number">

                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" id="edit_name" name="customer_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" id="edit_phone" name="customer_phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="12" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Shipping Address</label>
                            <div class="space-y-2">
                                <input type="text" id="edit_shipping_address_line1" name="billing_address_line1" placeholder="Address Line 1" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <input type="text" id="edit_shipping_address_line2" name="billing_address_line2" placeholder="Address Line 2" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" id="edit_shipping_city" name="billing_city" placeholder="City" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" id="edit_shipping_zipcode" name="billing_zipcode" placeholder="Zipcode" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <input type="text" id="edit_shipping_state" name="shipping_state" placeholder="State" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <select id="edit_shipping_state_select" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500"></select>
                                <select id="edit_shipping_country" name="billing_country" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <?php
                                    $selected_iso = strtoupper(trim((string)($orderremarks['shipping_country'] ?? 'IN')));
                                    $country_list = $countries;
                                    include __DIR__ . '/../pos_register/partials/iso_country_options.php';
                                    ?>
                                </select>
                                <input type="text" id="edit_shipping_gstin" name="shipping_gstin" placeholder="GSTIN (optional)" maxlength="15" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white uppercase focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Billing Address</label>
                            <div class="space-y-2">
                                <input type="text" id="edit_billing_address_line1" name="address_line1" placeholder="Address Line 1" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <input type="text" id="edit_billing_address_line2" name="address_line2" placeholder="Address Line 2" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" id="edit_billing_city" name="city" placeholder="City" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" id="edit_billing_zipcode" name="zipcode" placeholder="Zipcode" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <input type="text" id="edit_billing_state" name="state" placeholder="State" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <select id="edit_billing_state_select" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500"></select>
                                <select id="edit_billing_country" name="country" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <?php
                                    $selected_iso = strtoupper(trim((string)($orderremarks['country'] ?? 'IN')));
                                    $country_list = $countries;
                                    include __DIR__ . '/../pos_register/partials/iso_country_options.php';
                                    ?>
                                </select>
                                <input type="text" id="edit_billing_gstin" name="gstin" placeholder="GSTIN (optional)" maxlength="15" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white uppercase focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="closeNameEmailPopup()"
                class="rounded-full px-5 py-2.5 bg-gray-200 text-gray-800 hover:bg-gray-300 text-sm font-medium">
                Cancel
            </button>
            <button type="submit" form="nameEmailForm"
                class="rounded-full bg-[#D46B08] px-10 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-700">
                Save
            </button>
        </div>
    </div>
</div>

<div id="statusPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50 p-4" onclick="closeStatusPopup(event)">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative" onclick="event.stopPropagation();">
        <button type="button" onclick="closeStatusPopup()" class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <div class="grid grid-cols-1 md:grid-cols-[38%_62%] gap-0">
            <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200">
                <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md border h-36 w-full max-w-[220px] object-cover mb-4">
                <p class="text-sm text-gray-600 space-y-1">
                    <strong>Order Number:</strong> <span id="status_order_number"></span><br>
                    <strong>Item Code:</strong> <span id="status_item_code"></span><br>
                    <?php if ($showOrderVendorName): ?>
                    <strong>Vendor Name:</strong> <span id="status_vendor_name"></span><br>
                    <?php endif; ?>
                    <span id="status_category"></span> / <span id="status_sub_category"></span><br>
                    <span id="status_item" class="font-bold"></span>
                </p>
            </div>
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-4">Update Order</h2>
                <form id="statusForm" enctype="multipart/form-data" method="post" action="?page=orders&action=update_status">
                    <input type="hidden" name="status_order_id" id="status_order_id">
                    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="orderStatus" class="block text-gray-700 font-bold mb-2">Order Status</label>
                            <select id="orderStatus" name="orderStatus" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">-- Order Status --</option>
                                <?php renderPartial('views/shared/partials/order_status_select_options.php', [
                                    'order_status_list' => $order_status_list,
                                ]); ?>
                            </select>
                            <input type="hidden" id="previousStatus" name="previousStatus" value="">
                        </div>
                        <div>
                            <label for="statusESD" class="block text-gray-700 font-bold mb-2">Ship By Date</label>
                            <input type="date" id="statusESD" name="esd" class="border border-gray-300 rounded px-2 py-1.5 w-full">
                            <input type="hidden" id="previousESD" name="previous_esd" value="">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="agentId" class="block text-gray-700 font-bold mb-2">Assign agent</label>
                            <select name="agent_id" id="agentId" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">Select User</option>
                                <?php foreach ($staff_list as $id => $name): ?>
                                    <option value="<?= (int)$id ?>"><?= htmlspecialchars((string)$name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="agentName" name="agent_name" value="">
                            <input type="hidden" id="previousAgent" name="previous_agent" value="">
                        </div>
                        <div>
                            <label for="orderPriority" class="block text-gray-700 font-bold mb-2">Priority</label>
                            <select id="orderPriority" name="orderPriority" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">-Select-</option>
                                <option value="critical">Critical</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <input type="hidden" id="previousPriority" name="previous_priority" value="">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="orderRemarks" class="block text-gray-700 font-bold mb-2">Notes</label>
                        <textarea id="orderRemarks" name="orderRemarks" class="border border-gray-300 rounded px-3 py-2 w-full" rows="4"></textarea>
                        <input type="hidden" id="previousRemarks" name="previous_remarks" value="">
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Saving updates the local status and syncs to Exotic India when supported for this status.</p>
                    <div id="orderStatusError" class="text-red-500 text-sm mt-1 hidden">Please select a status.</div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeStatusPopup()" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openNoteEditPopup(orderNumber, currentRemarks) {
        document.getElementById('note_order_number').value = orderNumber;
        document.getElementById('note_remarks').value = currentRemarks || '';
        document.getElementById('noteEditPopup').classList.remove('hidden');
    }

    function closeNotePopup() {
        document.getElementById('noteEditPopup').classList.add('hidden');
        // Optional: clear form
        document.getElementById('note_remarks').value = '';
    }

    document.getElementById('noteEditForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const orderNumber = document.getElementById('note_order_number').value;
        const remarks = document.getElementById('note_remarks').value.trim();

        if (!orderNumber) {
            alert("Order number is missing.");
            return;
        }

        fetch('index.php?page=orders&action=update_note_ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_number=${encodeURIComponent(orderNumber)}&remarks=${encodeURIComponent(remarks)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update displayed note
                    const displayEl = document.getElementById('note-display-' + orderNumber);
                    if (displayEl) {
                        if (remarks.trim()) {
                            displayEl.innerHTML = nl2br(escapeHtml(remarks));
                        } else {
                            displayEl.innerHTML = '<em class="text-black-400">No notes from customer</em>';
                        }
                    }

                    // Optional success feedback
                    alert("Note updated successfully!");
                    closeNotePopup();
                    window.location.reload();
                } else {
                    alert("Failed to update note: " + (data.message || "Unknown error"));
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error communicating with server.");
            });
    });

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function nl2br(str) {
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>');
    }

    const ORDER_STATE_FIELD_CONFIG = {
        shipping: { countryId: 'edit_shipping_country', inputId: 'edit_shipping_state', selectId: 'edit_shipping_state_select' },
        billing: { countryId: 'edit_billing_country', inputId: 'edit_billing_state', selectId: 'edit_billing_state_select' }
    };

    function isOrderStateDropdownCountry(code) {
        const c = String(code || '').trim().toUpperCase().substring(0, 2);
        return c === 'IN' || c === 'US';
    }

    function fetchOrderCountryStates(countryCode) {
        const country = String(countryCode || 'IN').trim().toUpperCase().substring(0, 2) || 'IN';
        window.ORDER_COUNTRY_STATES = window.ORDER_COUNTRY_STATES || {};
        if (Array.isArray(window.ORDER_COUNTRY_STATES[country]) && window.ORDER_COUNTRY_STATES[country].length) {
            return Promise.resolve(window.ORDER_COUNTRY_STATES[country]);
        }

        return fetch('index.php?page=pos_register&action=states-by-country&country=' + encodeURIComponent(country), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                window.ORDER_COUNTRY_STATES[country] = Array.isArray(data) ? data : [];
                return window.ORDER_COUNTRY_STATES[country];
            })
            .catch(() => {
                window.ORDER_COUNTRY_STATES[country] = [];
                return [];
            });
    }

    function populateOrderStateSelect(selectEl, states, selectedValue) {
        if (!selectEl) return;
        const selected = String(selectedValue || '').trim();
        const selectedLower = selected.toLowerCase();
        let html = '<option value="">Select state</option>';
        (states || []).forEach(state => {
            const name = String((state && state.name) || '').trim();
            if (!name) return;
            const esc = name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            html += '<option value="' + esc + '">' + esc + '</option>';
        });
        selectEl.innerHTML = html;
        if (selected) {
            let matched = false;
            Array.from(selectEl.options).forEach(opt => {
                if (opt.value.toLowerCase() === selectedLower) {
                    opt.selected = true;
                    matched = true;
                }
            });
            if (!matched) {
                const opt = document.createElement('option');
                opt.value = selected;
                opt.textContent = selected;
                opt.selected = true;
                selectEl.appendChild(opt);
            }
        }
    }

    function resetOrderStateSelect(selectEl, message) {
        if (!selectEl) return;
        const label = message || 'Select state';
        const esc = label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        selectEl.innerHTML = '<option value="">' + esc + '</option>';
        selectEl.value = '';
    }

    function getOrderStateValue(kind) {
        const cfg = ORDER_STATE_FIELD_CONFIG[kind];
        if (!cfg) return '';
        const selectEl = document.getElementById(cfg.selectId);
        const inputEl = document.getElementById(cfg.inputId);
        if (selectEl && !selectEl.classList.contains('hidden')) {
            return String(selectEl.value || '').trim();
        }
        return inputEl ? String(inputEl.value || '').trim() : '';
    }

    function syncOrderStateField(kind, preferredValue) {
        const cfg = ORDER_STATE_FIELD_CONFIG[kind];
        if (!cfg) return Promise.resolve();
        const countryEl = document.getElementById(cfg.countryId);
        const inputEl = document.getElementById(cfg.inputId);
        const selectEl = document.getElementById(cfg.selectId);
        if (!countryEl || !inputEl || !selectEl) return Promise.resolve();

        const country = String(countryEl.value || 'IN').trim().toUpperCase().substring(0, 2) || 'IN';
        const useDropdown = isOrderStateDropdownCountry(country);
        const value = preferredValue !== undefined ? String(preferredValue || '').trim() : getOrderStateValue(kind);

        if (!useDropdown) {
            inputEl.value = value;
            selectEl.classList.add('hidden');
            inputEl.classList.remove('hidden');
            return Promise.resolve();
        }

        inputEl.value = '';
        resetOrderStateSelect(selectEl, 'Loading states...');
        inputEl.classList.add('hidden');
        selectEl.classList.remove('hidden');

        return fetchOrderCountryStates(country).then(states => {
            populateOrderStateSelect(selectEl, states, value);
        });
    }

    function openNameEmailPopup(orderNumber) {
        document.getElementById('edit_order_number').value = orderNumber;
        document.getElementById('edit_name').value = document.getElementById('display-customer-name')?.textContent.trim() || '';
        document.getElementById('edit_phone').value = document.getElementById('display-customer-phone')?.textContent.trim() || '';
        document.getElementById('edit_shipping_address_line1').value = document.getElementById('shipping_address1')?.textContent.trim() || '';
        document.getElementById('edit_shipping_address_line2').value = document.getElementById('shipping_address2')?.textContent.trim() || '';
        document.getElementById('edit_shipping_city').value = document.getElementById('shipping_city')?.textContent.trim() || '';
        document.getElementById('edit_shipping_zipcode').value = document.getElementById('shipping_zipcode')?.textContent.trim() || '';
        document.getElementById('edit_shipping_country').value = document.getElementById('shipping_country')?.dataset.code || 'IN';
        document.getElementById('edit_shipping_gstin').value = document.getElementById('shipping_gstin')?.textContent.trim() || '';
        document.getElementById('edit_billing_address_line1').value = document.getElementById('billing_address1')?.textContent.trim() || '';
        document.getElementById('edit_billing_address_line2').value = document.getElementById('billing_address2')?.textContent.trim() || '';
        document.getElementById('edit_billing_city').value = document.getElementById('billing_city')?.textContent.trim() || '';
        document.getElementById('edit_billing_zipcode').value = document.getElementById('billing_zipcode')?.textContent.trim() || '';
        document.getElementById('edit_billing_country').value = document.getElementById('billing_country')?.dataset.code || 'IN';
        document.getElementById('edit_billing_gstin').value = document.getElementById('billing_gstin')?.textContent.trim() || '';

        const shippingState = document.getElementById('shipping_state')?.textContent.trim() || '';
        const billingState = document.getElementById('billing_state')?.textContent.trim() || '';

        Promise.all([
            syncOrderStateField('shipping', shippingState),
            syncOrderStateField('billing', billingState)
        ]).then(() => {
            document.getElementById('nameEmailPopup').classList.remove('hidden');
        });
    }

    function closeNameEmailPopup() {
        document.getElementById('nameEmailPopup').classList.add('hidden');
    }

    document.getElementById('nameEmailForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const orderNumber = document.getElementById('edit_order_number').value;
        const name = document.getElementById('edit_name').value.trim();
        const phone = document.getElementById('edit_phone').value.trim();
        const address_line1 = document.getElementById('edit_billing_address_line1').value.trim();
        const address_line2 = document.getElementById('edit_billing_address_line2').value.trim();
        const city = document.getElementById('edit_billing_city').value.trim();
        const state = getOrderStateValue('billing');
        const zipcode = document.getElementById('edit_billing_zipcode').value.trim();
        const country = document.getElementById('edit_billing_country').value.trim();
        const billing_address_line1 = document.getElementById('edit_shipping_address_line1').value.trim();
        const billing_address_line2 = document.getElementById('edit_shipping_address_line2').value.trim();
        const billing_city = document.getElementById('edit_shipping_city').value.trim();
        const shipping_state = getOrderStateValue('shipping');
        const billing_zipcode = document.getElementById('edit_shipping_zipcode').value.trim();
        const billing_country = document.getElementById('edit_shipping_country').value.trim();
        const gstin = document.getElementById('edit_billing_gstin').value.trim().toUpperCase();
        const shipping_gstin = document.getElementById('edit_shipping_gstin').value.trim().toUpperCase();

        if (!name || !phone) {
            alert("All fields (Name, Email, Phone) are required.");
            return;
        }

        fetch('index.php?page=orders&action=update_name_email_ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_number=${encodeURIComponent(orderNumber)}&customer_name=${encodeURIComponent(name)}&customer_phone=${encodeURIComponent(phone)}&address_line1=${encodeURIComponent(address_line1)}&address_line2=${encodeURIComponent(address_line2)}&city=${encodeURIComponent(city)}&state=${encodeURIComponent(state)}&zipcode=${encodeURIComponent(zipcode)}&country=${encodeURIComponent(country)}&gstin=${encodeURIComponent(gstin)}&billing_address_line1=${encodeURIComponent(billing_address_line1)}&billing_address_line2=${encodeURIComponent(billing_address_line2)}&billing_city=${encodeURIComponent(billing_city)}&shipping_state=${encodeURIComponent(shipping_state)}&billing_zipcode=${encodeURIComponent(billing_zipcode)}&billing_country=${encodeURIComponent(billing_country)}&shipping_gstin=${encodeURIComponent(shipping_gstin)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Customer information updated successfully!");
                    closeNameEmailPopup();

                    // Optional – safer for consistency with other parts of the page
                    window.location.reload();
                } else {
                    alert("Failed to save: " + (data.message || "Unknown error"));
                }
            })
            .catch(() => {
                alert("Connection problem. Please try again.");
            });
    });

    function openStatusPopup(orderId) {
        document.getElementById('status_order_id').value = orderId;
        document.getElementById('statusPopup').classList.remove('hidden');
        document.getElementById('orderStatusError').textContent = '';
        document.getElementById('orderStatusError').classList.add('hidden');
        document.getElementById('orderRemarks').value = '';
        document.getElementById('orderPriority').value = '';

        const orderEl = document.querySelector('#order-id-' + orderId);
        if (!orderEl) {
            alert('Order data not found.');
            return;
        }
        const orderData = JSON.parse(orderEl.getAttribute('data-order'));
        document.getElementById('orderRemarks').value = orderData.remarks || '';
        document.getElementById('orderStatus').value = orderData.status || '';
        document.getElementById('status_order_number').textContent = orderData.order_number || 'N/A';
        document.getElementById('status_item_code').textContent = orderData.item_code || 'N/A';
        <?php if ($showOrderVendorName): ?>
        document.getElementById('status_vendor_name').textContent = orderData.vendor_name || orderData.vendor || 'N/A';
        <?php endif; ?>
        document.getElementById('status_category').textContent = orderData.groupname || 'N/A';
        document.getElementById('status_sub_category').textContent = orderData.subcategories || 'N/A';
        document.getElementById('status_item').textContent = orderData.title || 'N/A';
        document.getElementById('orderPriority').value = orderData.priority || '';
        document.getElementById('previousStatus').value = orderData.status || '';
        document.getElementById('previousAgent').value = orderData.agent_id || '';
        document.getElementById('agentId').value = orderData.agent_id || '';
        document.getElementById('previousPriority').value = orderData.priority || '';
        document.getElementById('previousRemarks').value = orderData.remarks || '';
        document.getElementById('previousESD').value = orderData.esd || '';

        const statusESD = document.getElementById('statusESD');
        const raw = orderData.esd || '';
        if (statusESD) {
            const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            statusESD.value = m ? raw : (raw || '');
        }

        const imgElem = document.querySelector('#statusPopup img');
        if (imgElem) {
            imgElem.src = orderData.image || 'https://placehold.co/100x80/e2e8f0/4a5568?text=Item';
        }
    }

    function closeStatusPopup(e) {
        if (e && e.target && e.currentTarget !== e.target) {
            return;
        }
        document.getElementById('statusPopup').classList.add('hidden');
    }

    document.getElementById('agentId')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('agentName').value = selectedOption.text;
    });

    document.getElementById('statusForm')?.addEventListener('submit', function(e) {
        const statusSelect = document.getElementById('orderStatus');
        const errorDiv = document.getElementById('orderStatusError');
        if (statusSelect.value === '') {
            e.preventDefault();
            errorDiv.classList.remove('hidden');
            return;
        }
        errorDiv.classList.add('hidden');
        e.preventDefault();
        const formData = new FormData(document.getElementById('statusForm'));
        fetch('index.php?page=orders&action=update_status', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order status updated successfully.');
                    closeStatusPopup();
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.message || 'Error updating order status.';
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(function() {
                alert('An error occurred while updating order status.');
            });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const accordionTriggers = document.querySelectorAll('.accordion-trigger');
        accordionTriggers.forEach(trigger => {
            // Remove previous handler if stored to avoid duplicate handlers
            if (trigger.__accordionClick__) {
                trigger.removeEventListener('click', trigger.__accordionClick__);
            }

            const handler = function() {
                const content = this.nextElementSibling;
                const isOpening = !content.classList.contains('open');

                // Open or close the clicked one
                if (isOpening) {
                    content.classList.add('open');
                    this.classList.add('active');
                } else {
                    content.classList.remove('open');
                    this.classList.remove('active');
                }
            };

            // store the handler reference so it can be removed later
            trigger.__accordionClick__ = handler;
            trigger.addEventListener('click', handler);
        });

        document.getElementById('edit_shipping_country')?.addEventListener('change', function() {
            syncOrderStateField('shipping', '');
        });
        document.getElementById('edit_billing_country')?.addEventListener('change', function() {
            syncOrderStateField('billing', '');
        });
    });
</script>