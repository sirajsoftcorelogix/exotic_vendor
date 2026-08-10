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
$orderCurrencyCode = strtoupper(trim((string)($order[0]['currency'] ?? 'INR')));
if ($orderCurrencyCode === '') {
    $orderCurrencyCode = 'INR';
}
$orderCurrencySymbol = vendor_currency_symbol($orderCurrencyCode);
$canEditOrderPrices = !empty($canEditOrderPrices) || (function_exists('canSrEmpAccess') && canSrEmpAccess());
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

<div class="min-h-screen bg-gray-50 p-6 font-sans text-gray-900">
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
            <?php if ($canEditOrderPrices): ?>
                <button type="button" onclick="openEditPricesModal()" class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50 transition-colors">Edit</button>
            <?php else: ?>
                <button type="button" disabled title="Sr Emp or higher access required to edit prices" class="rounded border bg-gray-100 text-gray-400 px-4 py-1.5 text-sm font-medium cursor-not-allowed">Edit</button>
            <?php endif; ?>
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
                            class="flex items-center gap-2 rounded bg-[#E5E7EB] px-3 py-1 text-xs font-medium text-gray-600">
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
                            <div class="flex items-center gap-2 rounded bg-[#E5E7EB] px-3 py-1 text-xs font-medium text-gray-600">
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
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span
                            class="text-sm font-medium text-gray-600"><?php echo date('d-M-Y', strtotime($orderremarks['created_at'] ?? '')); ?></span>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php foreach ($order as $item):
                        $currencysymbol = vendor_currency_symbol($item['currency'] ?? $orderCurrencyCode);
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
                                    <!-- <h4 class="mb-3 text-[12px] font-semibold leading-tight text-gray-900">
                                    <?php echo $item['groupname']; ?> / <?php echo $item['subcategories']; ?>
                                </h4> -->
                                    <h4 class="mb-3 text-[14px] leading-tight text-gray-900">
                                        <?php echo $item['title']; ?>
                                    </h4>

                                    <div class="flex justify-between items-start">
                                        <div class="space-y-1.5 text-[13px]">
                                            <p>
                                                <span class="inline-block w-12 font-bold text-black">SKU</span>
                                                <span class="text-black">:</span>
                                                <span class="ml-2 text-gray-700"><?php echo $item['sku']; ?></span>
                                            </p>
                                            <p>
                                                <span class="inline-block w-12 font-bold text-black">Color</span>
                                                <span class="text-black">:</span>
                                                <span class="ml-2 text-gray-700"><?php echo $item['color']; ?></span>
                                            </p>
                                            <div class="flex items-center pt-1">
                                                <span class="inline-block w-12 font-bold text-black">Qty.</span>
                                                <span class="text-black">:</span>
                                                <span
                                                    class="ml-4 rounded-full border border-gray-200 bg-gray-50 px-5 py-0.5 text-gray-800">
                                                    <?php echo str_pad($item['quantity'], 2, '0', STR_PAD_LEFT); ?>
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-1 gap-1 pt-2 text-[12px] text-gray-600">
                                                <p><span class="font-bold text-black">Priority</span>: <?php echo $linePriority !== '' ? htmlspecialchars(ucfirst($linePriority)) : 'â€”'; ?></p>
                                                <p><span class="font-bold text-black">Agent</span>: <?php echo htmlspecialchars($lineAgentName); ?></p>
                                                <p><span class="font-bold text-black">Ship by</span>: <?php echo $lineEsd !== '' ? htmlspecialchars(date('d M Y', strtotime($lineEsd))) : 'â€”'; ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <div class="flex items-center gap-2 text-[13px] text-gray-500">
                                                <span><?php echo $currencysymbol; ?><?php echo $item['finalprice']; ?> x</span>
                                                <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700"><?php echo $item['quantity']; ?></span>
                                            </div>

                                            <div class="w-20 text-right text-[14px] font-bold text-gray-900">
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
                    $unit_price = floatval($item['finalprice'] ?? 0);   // â† Pre-GST unit price
                    $gst_percent = floatval($item['gst'] ?? 0);         // â† GST percentage from DB
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
                                <div class="col-span-3 font-bold text-gray-800">Subtotal</div>
                                <div class="col-span-6 text-gray-500"><?php echo count($order); ?> items</div>
                                <div class="col-span-3 text-right font-bold text-gray-900">
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
                                <div class="col-span-3 font-bold text-gray-800">Taxes</div>
                                <div class="col-span-6 text-gray-500">SGST + CGST</div>
                                <div class="col-span-3 text-right font-bold text-gray-900">
                                    <?php echo $currencysymbol; ?><?php echo number_format($tax_amount, 2); ?>
                                </div>
                            </div>
                            <!-- Final Total -->
                            <div class="grid grid-cols-12 items-start text-sm pt-1 border-t border-gray-200 pt-3">
                                <div class="col-span-3 font-bold text-gray-800">Total</div>
                                <div class="col-span-6"></div>
                                <div class="col-span-3 text-right font-bold text-gray-900 text-lg">
                                    <?php echo $currencysymbol; ?><?php echo number_format($final_paid, 2); ?>
                                </div>
                            </div>
                        </div>
                        <div class="bg-[#F9FAFB] border-t border-gray-200 p-6 flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-800">Paid</span>
                            <span class="text-sm font-bold text-gray-900">
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
                        class="absolute top-4 right-4 text-gray-500 hover:text-blue-600 transition-colors"
                        title="Edit addresses">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                    <h3 class="mb-4 text-sm font-bold text-gray-700">Shipping &amp; Billing Address</h3>
                    <?php
                    $customerNameParts = preg_split('/\s+/', trim((string)($customerdetails['customer_name'] ?? '')), 2);
                    $fallbackFirstName = trim((string)($customerNameParts[0] ?? ''));
                    $fallbackLastName = trim((string)($customerNameParts[1] ?? ''));

                    $billingFirstName = trim((string)($orderremarks['first_name'] ?? ''));
                    $billingLastName = trim((string)($orderremarks['last_name'] ?? ''));
                    $shippingFirstName = trim((string)($orderremarks['shipping_first_name'] ?? ''));
                    $shippingLastName = trim((string)($orderremarks['shipping_last_name'] ?? ''));

                    if ($billingFirstName === '' && $billingLastName === '') {
                        $billingFirstName = $fallbackFirstName;
                        $billingLastName = $fallbackLastName;
                    }
                    if ($shippingFirstName === '' && $shippingLastName === '') {
                        $shippingFirstName = $billingFirstName;
                        $shippingLastName = $billingLastName;
                    }
                    $billingDisplayName = trim($billingFirstName . ' ' . $billingLastName);
                    $shippingDisplayName = trim($shippingFirstName . ' ' . $shippingLastName);

                    $customerPhone = trim((string)($orderremarks['mobile'] ?? ($orderremarks['shipping_mobile'] ?? ($customerdetails['customer_phone'] ?? ''))));
                    $customerName = $billingDisplayName !== '' ? $billingDisplayName : ($shippingDisplayName !== '' ? $shippingDisplayName : trim((string)($customerdetails['customer_name'] ?? '')));

                    $billingAddress1 = trim((string)($orderremarks['address_line1'] ?? ''));
                    $billingAddress2 = trim((string)($orderremarks['address_line2'] ?? ''));
                    $billingCity = trim((string)($orderremarks['city'] ?? ''));
                    $billingState = trim((string)($orderremarks['state'] ?? ''));
                    $billingZipcode = trim((string)($orderremarks['zipcode'] ?? ''));
                    $billingCountry = trim((string)($orderremarks['country'] ?? 'IN'));
                    $billingMobile = trim((string)($orderremarks['mobile'] ?? ''));
                    $billingGstin = trim((string)($orderremarks['gstin'] ?? ''));

                    $shippingAddress1 = trim((string)($orderremarks['shipping_address_line1'] ?? ''));
                    $shippingAddress2 = trim((string)($orderremarks['shipping_address_line2'] ?? ''));
                    $shippingCity = trim((string)($orderremarks['shipping_city'] ?? ''));
                    $shippingState = trim((string)($orderremarks['shipping_state'] ?? ''));
                    $shippingZipcode = trim((string)($orderremarks['shipping_zipcode'] ?? ''));
                    $shippingCountry = trim((string)($orderremarks['shipping_country'] ?? ''));
                    $shippingMobile = trim((string)($orderremarks['shipping_mobile'] ?? ''));
                    $shippingGstin = trim((string)($orderremarks['shipping_gstin'] ?? ''));

                    if ($shippingAddress1 === '' && $shippingCity === '') {
                        $shippingAddress1 = $billingAddress1;
                        $shippingAddress2 = $billingAddress2;
                        $shippingCity = $billingCity;
                        $shippingState = $billingState;
                        $shippingZipcode = $billingZipcode;
                        $shippingCountry = $billingCountry !== '' ? $billingCountry : 'IN';
                        if ($shippingMobile === '') {
                            $shippingMobile = $billingMobile;
                        }
                        if ($shippingGstin === '') {
                            $shippingGstin = $billingGstin;
                        }
                    }
                    if ($shippingCountry === '') {
                        $shippingCountry = $billingCountry !== '' ? $billingCountry : 'IN';
                    }
                    ?>
                    <span id="display-customer-name" class="hidden"><?php echo htmlspecialchars($customerName); ?></span>
                    <span id="display-customer-phone" class="hidden"><?php echo htmlspecialchars($customerPhone); ?></span>
                    <span id="billing_first_name" class="hidden"><?php echo htmlspecialchars($billingFirstName); ?></span>
                    <span id="billing_last_name" class="hidden"><?php echo htmlspecialchars($billingLastName); ?></span>
                    <span id="shipping_first_name" class="hidden"><?php echo htmlspecialchars($shippingFirstName); ?></span>
                    <span id="shipping_last_name" class="hidden"><?php echo htmlspecialchars($shippingLastName); ?></span>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400">Shipping address</h4>
                            <address class="mt-2 text-sm not-italic text-gray-800 leading-relaxed">
                                <?php if ($shippingDisplayName !== ''): ?>
                                    <span class="block font-medium" id="shipping_display_name"><?php echo htmlspecialchars($shippingDisplayName); ?></span>
                                <?php else: ?>
                                    <span class="block font-medium hidden" id="shipping_display_name"></span>
                                <?php endif; ?>
                                <span id="shipping_address1"><?php echo htmlspecialchars($shippingAddress1); ?></span>
                                <?php if ($shippingAddress2 !== ''): ?>
                                    <br><span id="shipping_address2"><?php echo htmlspecialchars($shippingAddress2); ?></span>
                                <?php else: ?>
                                    <span id="shipping_address2" class="hidden"></span>
                                <?php endif; ?>
                                <br>
                                <span id="shipping_city"><?php echo htmlspecialchars($shippingCity); ?></span><?php if ($shippingState !== ''): ?>,
                                    <span id="shipping_state"><?php echo htmlspecialchars($shippingState); ?></span><?php else: ?><span id="shipping_state" class="hidden"></span><?php endif; ?>
                                <?php if ($shippingZipcode !== ''): ?>
                                    - <span id="shipping_zipcode"><?php echo htmlspecialchars($shippingZipcode); ?></span>
                                <?php else: ?>
                                    <span id="shipping_zipcode" class="hidden"></span>
                                <?php endif; ?>
                                <?php if ($shippingCountry !== ''): ?>
                                    <br><span id="shipping_country" data-code="<?php echo htmlspecialchars($shippingCountry); ?>"><?php echo htmlspecialchars($resolveCountryLabel($shippingCountry)); ?></span>
                                <?php else: ?>
                                    <span id="shipping_country" class="hidden"></span>
                                <?php endif; ?>
                                <?php if ($shippingMobile !== ''): ?>
                                    <br><span id="shipping_mobile" class="mt-1 block"><?php echo htmlspecialchars($shippingMobile); ?></span>
                                <?php else: ?>
                                    <span id="shipping_mobile" class="hidden"></span>
                                <?php endif; ?>
                                <?php if ($shippingGstin !== ''): ?>
                                    <br><span class="text-xs text-gray-500">GSTIN:</span> <span id="shipping_gstin"><?php echo htmlspecialchars($shippingGstin); ?></span>
                                <?php else: ?>
                                    <span id="shipping_gstin" class="hidden"></span>
                                <?php endif; ?>
                            </address>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400">Billing address</h4>
                            <address class="mt-2 text-sm not-italic text-gray-800 leading-relaxed">
                                <?php if ($billingDisplayName !== ''): ?>
                                    <span class="block font-medium" id="billing_display_name"><?php echo htmlspecialchars($billingDisplayName); ?></span>
                                <?php else: ?>
                                    <span class="block font-medium hidden" id="billing_display_name"></span>
                                <?php endif; ?>
                                <span id="billing_address1"><?php echo htmlspecialchars($billingAddress1); ?></span>
                                <?php if ($billingAddress2 !== ''): ?>
                                    <br><span id="billing_address2"><?php echo htmlspecialchars($billingAddress2); ?></span>
                                <?php else: ?>
                                    <span id="billing_address2" class="hidden"></span>
                                <?php endif; ?>
                                <br>
                                <span id="billing_city"><?php echo htmlspecialchars($billingCity); ?></span><?php if ($billingState !== ''): ?>,
                                    <span id="billing_state"><?php echo htmlspecialchars($billingState); ?></span><?php else: ?><span id="billing_state" class="hidden"></span><?php endif; ?>
                                <?php if ($billingZipcode !== ''): ?>
                                    - <span id="billing_zipcode"><?php echo htmlspecialchars($billingZipcode); ?></span>
                                <?php else: ?>
                                    <span id="billing_zipcode" class="hidden"></span>
                                <?php endif; ?>
                                <?php if ($billingCountry !== ''): ?>
                                    <br><span id="billing_country" data-code="<?php echo htmlspecialchars($billingCountry); ?>"><?php echo htmlspecialchars($resolveCountryLabel($billingCountry)); ?></span>
                                <?php else: ?>
                                    <span id="billing_country" class="hidden"></span>
                                <?php endif; ?>
                                <?php if ($billingMobile !== ''): ?>
                                    <br><span id="billing_mobile" class="mt-1 block"><?php echo htmlspecialchars($billingMobile); ?></span>
                                <?php else: ?>
                                    <span id="billing_mobile" class="hidden"></span>
                                <?php endif; ?>
                                <?php if ($billingGstin !== ''): ?>
                                    <br><span class="text-xs text-gray-500">GSTIN:</span> <span id="billing_gstin"><?php echo htmlspecialchars($billingGstin); ?></span>
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
                <button type="button" onclick="openNoteEditPopup('<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>','<?= htmlspecialchars($orderremarks['remarks'] ?? '', ENT_QUOTES) ?>')" class="absolute top-4 right-4 text-gray-500 hover:text-blue-600 transition-colors" title="Edit Note">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <h3 class="mb-2 text-sm font-bold text-gray-700">Note</h3>
                <?php if (!empty($orderremarks['remarks'])): ?>
                    <div id="note-display-<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>" class="text-sm text-gray-700 max-h-[180px] overflow-y-auto break-words leading-relaxed bg-gray-50 p-3 rounded-md border border-gray-200">
                        <?php echo ($orderremarks['remarks']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Conversion Summary -->
            <?php if (!empty($orderremarks['payment_type']) || !empty($orderremarks['country'])): ?>
                <div class="rounded-lg border bg-white p-5 shadow-sm relative">
                    <h3 class="mb-2 text-sm font-bold text-gray-700">Conversion Summary</h3>
                    <div
                        class="text-sm text-gray-700 max-h-[180px] overflow-y-auto break-words leading-relaxed bg-gray-50 p-3 rounded-md border border-gray-200">
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
        <button onclick="closeNotePopup()" class="absolute top-3 right-4 text-gray-500 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Customer Note</h2>

        <form id="noteEditForm">
            <input type="hidden" id="note_order_number" name="order_number">

            <textarea id="note_remarks" name="remarks" rows="6"
                class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y"
                placeholder="Enter note / remarks here..."></textarea>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeNotePopup()" class="rounded-full px-5 py-2.5 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" id="edit_phone" name="customer_phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="12" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Shipping Address</label>
                            <div class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" id="edit_shipping_first_name" name="shipping_first_name" placeholder="First Name" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" id="edit_shipping_last_name" name="shipping_last_name" placeholder="Last Name" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
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
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" id="edit_billing_first_name" name="first_name" placeholder="First Name *" required class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" id="edit_billing_last_name" name="last_name" placeholder="Last Name *" required class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
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

<?php renderPartial('views/shared/partials/order_status_update_popup.php', [
    'order_status_list' => $order_status_list ?? [],
    'staff_list' => $staff_list ?? [],
    'showOrderVendorName' => !empty($showOrderVendorName),
    'orderPage' => 'orders',
]); ?>

<!-- Edit Order Item Prices Modal -->
<div id="editOrderPricesPopup" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-auto flex flex-col max-h-[90vh] relative overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Edit Order Details & Prices</h2>
                <p class="text-xs text-gray-500">Order #<?php echo htmlspecialchars($displayOrderNumber); ?></p>
            </div>
            <button type="button" onclick="closeEditPricesModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto p-6 custom-scrollbar flex-1">
            <form id="editOrderPricesForm">
                <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($displayOrderNumber); ?>">

                <div class="space-y-4">
                    <?php foreach ($order as $item): ?>
                        <?php
                            $lineId = (int)($item['id'] ?? 0);
                            $imageUrl = (string)($item['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=No+Image');
                            $itemCode = (string)($item['item_code'] ?? '');
                            $sku = (string)($item['sku'] ?? $itemCode);
                            $title = (string)($item['title'] ?? '');
                            $qty = (int)($item['quantity'] ?? 1);
                            $price = (float)($item['finalprice'] ?? 0);
                            $size = (string)($item['size'] ?? '');
                            $color = (string)($item['color'] ?? '');
                        ?>
                        <div class="flex items-center gap-4 rounded-xl border border-gray-200 p-4 bg-gray-50/50 hover:bg-white transition-colors">
                            <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="h-full w-full object-cover" alt="Product">
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 truncate" title="<?php echo htmlspecialchars($title); ?>">
                                    <?php echo htmlspecialchars($title); ?>
                                </h4>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
                                    <span><strong class="text-gray-800">SKU:</strong> <?php echo htmlspecialchars($sku !== '' ? $sku : '—'); ?></span>
                                    <span><strong class="text-gray-800">Item Code:</strong> <?php echo htmlspecialchars($itemCode); ?></span>
                                    <?php if ($color !== ''): ?>
                                        <span><strong class="text-gray-800">Color:</strong> <?php echo htmlspecialchars($color); ?></span>
                                    <?php endif; ?>
                                    <?php if ($size !== ''): ?>
                                        <span><strong class="text-gray-800">Size:</strong> <?php echo htmlspecialchars($size); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex flex-col items-start gap-1">
                                    <label class="text-xs font-semibold text-gray-700">Qty</label>
                                    <input type="number"
                                           step="1"
                                           min="1"
                                           required
                                           class="edit-qty-input w-20 rounded-md border border-gray-300 px-2.5 py-1.5 text-center font-semibold text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                           data-line-id="<?php echo $lineId; ?>"
                                           name="items[<?php echo $lineId; ?>][qty]"
                                           value="<?php echo $qty; ?>">
                                </div>

                                <div class="flex flex-col items-end gap-1">
                                    <label class="text-xs font-semibold text-gray-700">Unit Final Price (<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?>)</label>
                                    <div class="relative rounded-md shadow-xs">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               required
                                               class="edit-price-input w-28 sm:w-32 rounded-md border border-gray-300 px-3 py-1.5 text-right font-semibold text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                               data-line-id="<?php echo $lineId; ?>"
                                               name="items[<?php echo $lineId; ?>][price]"
                                               value="<?php echo htmlspecialchars(number_format($price, 2, '.', '')); ?>">
                                        <input type="hidden" name="items[<?php echo $lineId; ?>][id]" value="<?php echo $lineId; ?>">
                                        <input type="hidden" name="items[<?php echo $lineId; ?>][item_code]" value="<?php echo htmlspecialchars($itemCode); ?>">
                                        <input type="hidden" name="items[<?php echo $lineId; ?>][size]" value="<?php echo htmlspecialchars($size); ?>">
                                        <input type="hidden" name="items[<?php echo $lineId; ?>][color]" value="<?php echo htmlspecialchars($color); ?>">
                                    </div>
                                    <span class="text-[11px] text-gray-500">
                                        Line Total: <?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span class="line-calc-total font-medium"><?php echo number_format($price * $qty, 2); ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php
                    $existingCouponCode = trim((string)($orderremarks['coupon'] ?? ($order[0]['coupon'] ?? '')));
                    $existingCouponReduce = max(0.0, round((float)($orderremarks['coupon_reduce'] ?? ($order[0]['coupon_reduce'] ?? 0)), 2));

                    $existingGiftVoucherCode = trim((string)($orderremarks['giftvoucher'] ?? ($order[0]['giftvoucher'] ?? '')));
                    $existingGiftVoucherReduce = max(0.0, round((float)($orderremarks['giftvoucher_reduce'] ?? ($order[0]['giftvoucher_reduce'] ?? 0)), 2));

                    $existingCustomReduce = max(0.0, round((float)($orderremarks['custom_reduce'] ?? ($order[0]['custom_reduce'] ?? 0)), 2));

                    $existingCredit = max(0.0, round((float)($orderremarks['credit'] ?? ($order[0]['credit'] ?? 0)), 2));

                    $hasAppliedReductions = ($existingCouponReduce > 0 || $existingGiftVoucherReduce > 0 || $existingCredit > 0);
                ?>

                <div class="mt-5 space-y-3">
                    <?php if ($hasAppliedReductions): ?>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100 pb-1">Applied Discounts & Reductions</h3>
                    <?php endif; ?>

                    <?php if ($existingCouponReduce > 0): ?>
                        <div class="rounded-xl border border-green-200 bg-green-50/50 p-3.5 flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-green-100 text-green-800 uppercase tracking-wide">Coupon</span>
                                    <?php if ($existingCouponCode !== ''): ?>
                                        <strong class="text-xs font-bold text-green-900"><?php echo htmlspecialchars($existingCouponCode); ?></strong>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[11px] text-green-700 mt-0.5">Applied coupon discount</p>
                            </div>
                            <div class="text-right font-bold text-sm text-green-800">
                                -<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><?php echo number_format($existingCouponReduce, 2); ?>
                                <input type="hidden" id="edit-coupon-reduce-val" value="<?php echo htmlspecialchars(number_format($existingCouponReduce, 2, '.', '')); ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="edit-coupon-reduce-val" value="0.00">
                    <?php endif; ?>

                    <?php if ($existingGiftVoucherReduce > 0): ?>
                        <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-3.5 flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-100 text-purple-800 uppercase tracking-wide">Gift Voucher</span>
                                    <?php if ($existingGiftVoucherCode !== ''): ?>
                                        <strong class="text-xs font-bold text-purple-900"><?php echo htmlspecialchars($existingGiftVoucherCode); ?></strong>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[11px] text-purple-700 mt-0.5">Applied gift voucher discount</p>
                            </div>
                            <div class="text-right font-bold text-sm text-purple-800">
                                -<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><?php echo number_format($existingGiftVoucherReduce, 2); ?>
                                <input type="hidden" id="edit-giftvoucher-reduce-val" value="<?php echo htmlspecialchars(number_format($existingGiftVoucherReduce, 2, '.', '')); ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="edit-giftvoucher-reduce-val" value="0.00">
                    <?php endif; ?>

                    <?php if ($existingCredit > 0): ?>
                        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wide">Store Credit</span>
                                </div>
                                <p class="text-[11px] text-blue-700 mt-0.5">Store credit applied to order</p>
                            </div>
                            <div class="text-right font-bold text-sm text-blue-800">
                                -<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><?php echo number_format($existingCredit, 2); ?>
                                <input type="hidden" id="edit-credit-reduce-val" value="<?php echo htmlspecialchars(number_format($existingCredit, 2, '.', '')); ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="edit-credit-reduce-val" value="0.00">
                    <?php endif; ?>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Custom Reduce / Discount (<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?>)</label>
                            <p class="text-[11px] text-gray-500">Order-level custom price reduction</p>
                        </div>
                        <div class="relative">
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   id="edit-custom-reduce-input"
                                   name="custom_reduce"
                                   class="w-36 rounded-md border border-gray-300 px-3 py-1.5 text-right font-semibold text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                   value="<?php echo htmlspecialchars(number_format($existingCustomReduce, 2, '.', '')); ?>">
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="flex flex-col gap-1.5 text-sm text-gray-700 mb-4 divide-y divide-gray-100">
                        <div class="flex items-center justify-between pb-1">
                            <span>Items Gross Total:</span>
                            <strong class="font-semibold text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span id="edit-prices-calc-gross">0.00</span></strong>
                        </div>

                        <?php if ($existingCouponReduce > 0): ?>
                            <div class="flex items-center justify-between pt-1.5 text-green-700">
                                <span>Coupon Discount <?php echo $existingCouponCode !== '' ? '(' . htmlspecialchars($existingCouponCode) . ')' : ''; ?>:</span>
                                <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span id="edit-prices-calc-coupon"><?php echo number_format($existingCouponReduce, 2); ?></span></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($existingGiftVoucherReduce > 0): ?>
                            <div class="flex items-center justify-between pt-1.5 text-purple-700">
                                <span>Gift Voucher <?php echo $existingGiftVoucherCode !== '' ? '(' . htmlspecialchars($existingGiftVoucherCode) . ')' : ''; ?>:</span>
                                <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span id="edit-prices-calc-giftvoucher"><?php echo number_format($existingGiftVoucherReduce, 2); ?></span></strong>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between pt-1.5 text-orange-700">
                            <span>Custom Discount:</span>
                            <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span id="edit-prices-calc-discount">0.00</span></strong>
                        </div>

                        <?php if ($existingCredit > 0): ?>
                            <div class="flex items-center justify-between pt-1.5 text-blue-700">
                                <span>Store Credit:</span>
                                <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span id="edit-prices-calc-credit"><?php echo number_format($existingCredit, 2); ?></span></strong>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between pt-2 text-base font-bold text-gray-900 border-t border-gray-200">
                            <span>Net Order Total:</span>
                            <strong class="text-base text-gray-900 font-bold"><?php echo htmlspecialchars($orderCurrencySymbol ?? '₹'); ?><span id="edit-prices-calc-total">0.00</span></strong>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeEditPricesModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" id="btn-save-item-prices" class="rounded-lg bg-[#D46B08] px-6 py-2 text-sm font-bold text-white shadow-xs hover:bg-orange-700 transition-colors">
                            Update Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditPricesModal() {
    const popup = document.getElementById('editOrderPricesPopup');
    if (popup) {
        popup.classList.remove('hidden');
        updateEditPricesCalculatedTotal();
    }
}

function closeEditPricesModal() {
    const popup = document.getElementById('editOrderPricesPopup');
    if (popup) {
        popup.classList.add('hidden');
    }
}

function updateEditPricesCalculatedTotal() {
    const form = document.getElementById('editOrderPricesForm');
    if (!form) return;

    let grossTotal = 0;
    const priceInputs = form.querySelectorAll('.edit-price-input');

    for (let i = 0; i < priceInputs.length; i++) {
        const priceInput = priceInputs[i];
        const rowContainer = priceInput.closest('.flex.items-center');
        const qtyInput = rowContainer ? rowContainer.querySelector('.edit-qty-input') : null;
        const qty = qtyInput ? (parseFloat(qtyInput.value) || 1) : 1;
        const price = parseFloat(priceInput.value) || 0;
        const lineTotal = price * qty;
        grossTotal += lineTotal;

        if (rowContainer) {
            const lineTotalSpan = rowContainer.querySelector('.line-calc-total');
            if (lineTotalSpan) {
                lineTotalSpan.textContent = lineTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }

    const customReduceInput = form.querySelector('#edit-custom-reduce-input');
    const customReduce = customReduceInput ? (parseFloat(customReduceInput.value) || 0) : 0;

    const couponReduce = parseFloat(form.querySelector('#edit-coupon-reduce-val')?.value) || 0;
    const giftVoucherReduce = parseFloat(form.querySelector('#edit-giftvoucher-reduce-val')?.value) || 0;
    const creditReduce = parseFloat(form.querySelector('#edit-credit-reduce-val')?.value) || 0;

    const netTotal = Math.max(0, grossTotal - customReduce - couponReduce - giftVoucherReduce - creditReduce);

    const grossSpan = form.querySelector('#edit-prices-calc-gross');
    if (grossSpan) grossSpan.textContent = grossTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const discountSpan = form.querySelector('#edit-prices-calc-discount');
    if (discountSpan) discountSpan.textContent = customReduce.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const couponSpan = form.querySelector('#edit-prices-calc-coupon');
    if (couponSpan) couponSpan.textContent = couponReduce.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const giftSpan = form.querySelector('#edit-prices-calc-giftvoucher');
    if (giftSpan) giftSpan.textContent = giftVoucherReduce.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const creditSpan = form.querySelector('#edit-prices-calc-credit');
    if (creditSpan) creditSpan.textContent = creditReduce.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const totalSpan = form.querySelector('#edit-prices-calc-total');
    if (totalSpan) totalSpan.textContent = netTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.getElementById('editOrderPricesForm')?.addEventListener('input', function(e) {
    if (e.target && (e.target.classList.contains('edit-price-input') || e.target.classList.contains('edit-qty-input') || e.target.id === 'edit-custom-reduce-input')) {
        updateEditPricesCalculatedTotal();
    }
});

document.getElementById('editOrderPricesForm')?.addEventListener('change', function(e) {
    if (e.target && (e.target.classList.contains('edit-price-input') || e.target.classList.contains('edit-qty-input') || e.target.id === 'edit-custom-reduce-input')) {
        updateEditPricesCalculatedTotal();
    }
});
    const totalSpan = document.getElementById('edit-prices-calc-total');
    if (totalSpan) {
        totalSpan.textContent = total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

document.getElementById('editOrderPricesForm')?.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('edit-price-input')) {
        updateEditPricesCalculatedTotal();
    }
});

document.getElementById('editOrderPricesForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-item-prices');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Updating...';
    }

    const formData = new FormData(this);

    fetch('index.php?page=orders&action=update_item_prices', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeEditPricesModal();
            window.location.reload();
        } else {
            alert("Failed to update: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        alert("An error occurred: " + err.message);
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Update Order';
        }
    });
});
</script>

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
                            displayEl.innerHTML = '<em class="text-gray-400">No notes from customer</em>';
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

    function populateOrderStateSelect(selectEl, states, selectedValue, cityName) {
        if (!selectEl) return;
        const selected = String(selectedValue || '').trim();
        const selectedLower = selected.toLowerCase();
        const cityLower = String(cityName || '').trim().toLowerCase();

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
                const optValLower = opt.value.toLowerCase();
                if (optValLower === selectedLower) {
                    opt.selected = true;
                    matched = true;
                }
            });

            if (!matched && states && states.length) {
                // Try matching by state code or iso (e.g. "WB" -> "West Bengal")
                states.forEach(state => {
                    if (matched) return;
                    const codeLower = String(state.code || state.iso || '').trim().toLowerCase();
                    if (codeLower && codeLower === selectedLower) {
                        const name = String(state.name || '').trim();
                        Array.from(selectEl.options).forEach(opt => {
                            if (opt.value.toLowerCase() === name.toLowerCase()) {
                                opt.selected = true;
                                matched = true;
                            }
                        });
                    }
                });
            }

            if (!matched) {
                // Check if selected value matches the city name
                if (cityLower && selectedLower === cityLower) {
                    // Stored value was actually the city name, leave dropdown as "Select state"
                    selectEl.value = '';
                } else {
                    const opt = document.createElement('option');
                    opt.value = selected;
                    opt.textContent = selected;
                    opt.selected = true;
                    selectEl.appendChild(opt);
                }
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

    function syncOrderStateField(kind, preferredValue, cityName) {
        const cfg = ORDER_STATE_FIELD_CONFIG[kind];
        if (!cfg) return Promise.resolve();
        const countryEl = document.getElementById(cfg.countryId);
        const inputEl = document.getElementById(cfg.inputId);
        const selectEl = document.getElementById(cfg.selectId);
        if (!countryEl || !inputEl || !selectEl) return Promise.resolve();

        const country = String(countryEl.value || 'IN').trim().toUpperCase().substring(0, 2) || 'IN';
        const useDropdown = isOrderStateDropdownCountry(country);
        const value = preferredValue !== undefined ? String(preferredValue || '').trim() : getOrderStateValue(kind);

        const currentCity = cityName !== undefined ? String(cityName || '').trim() : (
            kind === 'shipping'
                ? (document.getElementById('edit_shipping_city')?.value || '').trim()
                : (document.getElementById('edit_billing_city')?.value || '').trim()
        );

        if (!useDropdown) {
            inputEl.value = (value && currentCity && value.toLowerCase() === currentCity.toLowerCase()) ? '' : value;
            selectEl.classList.add('hidden');
            inputEl.classList.remove('hidden');
            return Promise.resolve();
        }

        inputEl.value = '';
        resetOrderStateSelect(selectEl, 'Loading states...');
        inputEl.classList.add('hidden');
        selectEl.classList.remove('hidden');

        return fetchOrderCountryStates(country).then(states => {
            populateOrderStateSelect(selectEl, states, value, currentCity);
            if (inputEl) inputEl.value = getOrderStateValue(kind);
        });
    }

    function openNameEmailPopup(orderNumber) {
        document.getElementById('edit_order_number').value = orderNumber;
        document.getElementById('edit_phone').value = document.getElementById('display-customer-phone')?.textContent.trim() || '';
        document.getElementById('edit_shipping_first_name').value = document.getElementById('shipping_first_name')?.textContent.trim() || '';
        document.getElementById('edit_shipping_last_name').value = document.getElementById('shipping_last_name')?.textContent.trim() || '';
        document.getElementById('edit_billing_first_name').value = document.getElementById('billing_first_name')?.textContent.trim() || '';
        document.getElementById('edit_billing_last_name').value = document.getElementById('billing_last_name')?.textContent.trim() || '';
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

        const shippingCity = document.getElementById('shipping_city')?.textContent.trim() || '';
        const shippingState = document.getElementById('shipping_state')?.textContent.trim() || '';

        const billingCity = document.getElementById('billing_city')?.textContent.trim() || '';
        const billingState = document.getElementById('billing_state')?.textContent.trim() || '';

        Promise.all([
            syncOrderStateField('shipping', shippingState, shippingCity),
            syncOrderStateField('billing', billingState, billingCity)
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
        const first_name = document.getElementById('edit_billing_first_name').value.trim();
        const last_name = document.getElementById('edit_billing_last_name').value.trim();
        const shipping_first_name = document.getElementById('edit_shipping_first_name').value.trim();
        const shipping_last_name = document.getElementById('edit_shipping_last_name').value.trim();
        const name = [first_name, last_name].filter(Boolean).join(' ');
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

        if (!first_name || !last_name || !phone) {
            alert("Billing first name, last name and phone are required.");
            return;
        }

        fetch('index.php?page=orders&action=update_name_email_ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_number=${encodeURIComponent(orderNumber)}&customer_name=${encodeURIComponent(name)}&customer_phone=${encodeURIComponent(phone)}&first_name=${encodeURIComponent(first_name)}&last_name=${encodeURIComponent(last_name)}&shipping_first_name=${encodeURIComponent(shipping_first_name)}&shipping_last_name=${encodeURIComponent(shipping_last_name)}&address_line1=${encodeURIComponent(address_line1)}&address_line2=${encodeURIComponent(address_line2)}&city=${encodeURIComponent(city)}&state=${encodeURIComponent(state)}&zipcode=${encodeURIComponent(zipcode)}&country=${encodeURIComponent(country)}&gstin=${encodeURIComponent(gstin)}&billing_address_line1=${encodeURIComponent(billing_address_line1)}&billing_address_line2=${encodeURIComponent(billing_address_line2)}&billing_city=${encodeURIComponent(billing_city)}&shipping_state=${encodeURIComponent(shipping_state)}&billing_zipcode=${encodeURIComponent(billing_zipcode)}&billing_country=${encodeURIComponent(billing_country)}&shipping_gstin=${encodeURIComponent(shipping_gstin)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Customer information updated successfully!");
                    closeNameEmailPopup();

                    // Optional â€“ safer for consistency with other parts of the page
                    window.location.reload();
                } else {
                    alert("Failed to save: " + (data.message || "Unknown error"));
                }
            })
            .catch(() => {
                alert("Connection problem. Please try again.");
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
        document.getElementById('edit_shipping_state_select')?.addEventListener('change', function() {
            const inputEl = document.getElementById('edit_shipping_state');
            if (inputEl) inputEl.value = this.value;
        });
        document.getElementById('edit_billing_state_select')?.addEventListener('change', function() {
            const inputEl = document.getElementById('edit_billing_state');
            if (inputEl) inputEl.value = this.value;
        });
    });
</script>
