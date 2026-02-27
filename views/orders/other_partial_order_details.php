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
$currencyIcons = [ 'INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥'];
?>

<div class="min-h-screen bg-gray-50 p-6 font-sans text-black-900">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold"><?php echo $orderremarks['order_number'] ?? ''; ?></h1>
            <!-- <span class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white">Paid</span>
            <span class="rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-white">Canceled</span>
            <span class="rounded-full bg-yellow-500 px-3 py-1 text-xs font-semibold text-white">Refunded</span>
            <span class="rounded-full bg-gray-400 px-3 py-1 text-xs font-semibold text-white">Unfulfilled</span>
            <span class="rounded-full bg-orange-600 px-3 py-1 text-xs font-semibold text-white">Fulfilled</span>
            <span class="rounded-full bg-black px-3 py-1 text-xs font-semibold text-white">Archived</span> -->
        </div>

        <div class="flex items-center gap-2">
            <button class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">Restock</button>
            <button class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">Return</button>
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
                        <div
                            class="flex items-center gap-2 rounded bg-[#E5E7EB] px-3 py-1 text-xs font-medium text-black-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Fulfilled (32)</span>
                        </div>
                        <div
                            class="flex items-center gap-2 rounded bg-[#E5E7EB] px-3 py-1 text-xs font-medium text-black-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path
                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            <span><?php echo $orderremarks['city'] ?? ''; ?>, <?php echo $orderremarks['state'] ?? ''; ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                        <svg class="h-5 w-5 text-black-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span
                            class="text-sm font-medium text-black-600"><?php echo date('d-M-Y', strtotime($orderremarks['created_at'] ?? '')) ; ?></span>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php foreach ($order as $item): 
                        $currencyCode = strtoupper(trim($item['currency'] ?? ''));
                        if (isset($currencyIcons[$currencyCode]) && $currencyIcons[$currencyCode] !== '') {
                            $currencysymbol = $currencyIcons[$currencyCode] ?? $currencyCode;
                        } else {
                            $currencysymbol = $currencyCode.' ';
                        }
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
                                    </div>
                                    <div class="flex items-center gap-12">
                                        <div class="flex items-center gap-2 text-[13px] text-black-500">
                                            <span><?php echo $currencysymbol; ?><?php echo $item['finalprice']; ?> x</span>
                                            <span class="rounded bg-gray-100 px-2 py-0.5 text-black-700"><?php echo $item['quantity']; ?></span>
                                        </div>

                                        <div class="w-20 text-right text-[14px] font-bold text-black-900">
                                            <?php echo $currencysymbol; ?><?php echo $item['finalprice'] * $item['quantity']; ?>
                                        </div>
                                        <div class="w-20 text-right text-[14px] font-bold text-black-900">
                                            <span
                                                class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white"><?php echo $item['status']; ?></span>
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
                                    <?php endforeach; endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
                    $tax_rate = 0.05; // 5% (SGST + CGST combined)

                    // Individual reduction values (use 0 if not set)
                    $coupon_reduce      = floatval($orderremarks['coupon_reduce']      ?? 0);
                    $giftvoucher_reduce = floatval($orderremarks['giftvoucher_reduce'] ?? 0);
                    $credit             = floatval($orderremarks['credit']             ?? 0);
                    // $custom_reduce   = floatval($orderremarks['custom_reduce']      ?? 0); // add if needed

                    $all_reductions = $coupon_reduce + $giftvoucher_reduce + $credit; // + $custom_reduce ...

                    // Final amount customer paid
                    $final_paid = floatval($orderremarks['total'] ?? 0);

                    // Amount before tax = taxable amount (after discounts)
                    $amount_before_tax = $final_paid / (1 + $tax_rate);

                    // Tax amount
                    $tax_amount = $final_paid - $amount_before_tax;

                    // Original subtotal before discounts
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
                                <div class="col-span-6 text-black-500">SGST + CGST 5% (Included)</div>
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
                <?php if(!empty($fullOrderJourny)){ ?>
                    <div class="space-y-4 mt-8">
                        <div class="py-6 bg-[#F9FAFB] border border-gray-100 rounded-xl">
                            <h5 class="text-[10px] font-bold uppercase tracking-widest text-[#8E959F] mb-8 px-8">ORDER JOURNEY</h5>

                            <div class="relative flex flex-col px-8 space-y-0">
                                <?php 
                                    $totalItems = count($fullOrderJourny);
                                    $currentIteration = 0;
                                    
                                    foreach($fullOrderJourny as $journey){ 
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
                <?php if(!empty($orderremarks['remarks'])): ?>
                <div id="note-display-<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>" class="text-sm text-black-700 max-h-[180px] overflow-y-auto break-words leading-relaxed bg-gray-50 p-3 rounded-md border border-gray-200">
                    <?php echo ($orderremarks['remarks']); ?>
                </div>
                <?php endif; ?>
            </div>
            <!-- Conversion Summary -->
            <?php if(!empty($orderremarks['payment_type']) || !empty($orderremarks['country'])): ?>
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
            <!-- address Section -->
            <?php /* <div class="rounded-lg border bg-white p-5 shadow-sm relative">
                <button type="button" onclick="openNameEmailPopup('<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>')" class="absolute top-4 right-4 text-black-500 hover:text-blue-600 transition-colors" title="Edit address">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <h3 class="mb-3 text-sm font-bold">Customer</h3>
                <p class="text-sm font-medium text-blue-600" id="display-customer-name"><?php echo $customerdetails['customer_name'] ?? 'N/A'; ?></p>
                <p class="text-sm text-black-500">12 orders</p>

                <div class="mt-6">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-black-400">Contact information</h4>
                    <p class="mt-1 text-sm text-blue-600"><?php echo $customerdetails['customer_email'] ?? 'N/A'; ?></p>
                    <p class="text-sm" id="display-customer-phone"><?php echo $customerdetails['customer_phone'] ?? 'N/A'; ?></p>
                </div>

                <div class="mt-6">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-black-400">Shipping address</h4>
                    <address class="text-sm not-italic text-black-800 leading-relaxed">
                        <span class="block font-medium"><?php echo $customerdetails['customer_name'] ?? 'N/A'; ?></span>
                        <span id="address1"><?php echo $orderremarks['address_line1'] ?? ''; ?></span>
                        <span id="address2"><?php echo $orderremarks['address_line2'] ?? ''; ?></span>
                        <br>
                        <span id="city"><?php echo $orderremarks['city'] ?? ''; ?></span> -
                        <span id="zipcode"><?php echo $orderremarks['zipcode'] ?? ''; ?></span>,
                        <span id="country"><?php echo $orderremarks['country'] ?? ''; ?></span>
                        <br>
                        <span id="customer_phone" class="mt-1 block"><?php echo $customerdetails['customer_phone'] ?? ''; ?></span>
                    </address>
                </div>

                <div class="mt-6">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-black-400">Billing Address</h4>
                    <address class="text-sm not-italic text-black-800 leading-relaxed">
                        <span id="billing_address1"><?php echo $orderremarks['shipping_address_line1'] ?? ''; ?></span>
                        <span id="billing_address2"><?php echo $orderremarks['shipping_address_line2'] ?? ''; ?></span>
                        <br>
                        <span id="billing_city_city"><?php echo $orderremarks['shipping_city'] ?? ''; ?></span> -
                        <span id="billing_city_zip"><?php echo $orderremarks['shipping_zipcode'] ?? ''; ?></span>, 
                        <span id="billing_country"><?php echo $orderremarks['shipping_country'] ?? ''; ?></span><br>
                        <span id="billing_mobile" class="mt-1 block"><?php echo $orderremarks['shipping_mobile'] ?? ''; ?></span>
                    </address>
                </div>

                <div class="mt-6 border-t pt-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-black-400">Conversion summary</h4>
                    <p class="mt-1 text-sm">This is their 11th order</p>
                    <button class="mt-2 text-sm text-blue-600 hover:underline">View map</button>
                </div>
            </div> */ ?>
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
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto flex flex-col max-h-[90vh] relative">
        
        <div class="p-6 pb-0">
            <button onclick="closeNameEmailPopup()" class="absolute top-3 right-4 text-gray-500 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <h2 class="text-lg font-bold mb-4 text-gray-800">Edit Customer Name & Email</h2>
        </div>

        <div class="overflow-y-auto p-6 pt-2 custom-scrollbar">
            <form id="nameEmailForm">
                <input type="hidden" id="edit_order_number" name="order_number">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="edit_name" name="customer_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" id="edit_phone" name="customer_phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="12" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <hr class="border-gray-100">
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Shipping Address</label>
                        <div class="space-y-2">
                            <input type="text" id="edit_address_line1" name="address_line1" placeholder="Address Line 1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <input type="text" id="edit_address_line2" name="address_line2" placeholder="Address Line 2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" id="edit_city" name="city" placeholder="City" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <input type="text" id="edit_zipcode" name="zipcode" placeholder="Zipcode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <input type="text" id="edit_country" name="country" placeholder="Country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Billing Address</label>
                        <div class="space-y-2">
                            <input type="text" id="edit_billing_address_line1" name="billing_address_line1" placeholder="Address Line 1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <input type="text" id="edit_billing_address_line2" name="billing_address_line2" placeholder="Address Line 2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" id="edit_billing_city" name="billing_city" placeholder="City" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <input type="text" id="edit_billing_zipcode" name="billing_zipcode" placeholder="Zipcode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <input type="text" id="edit_billing_country" name="billing_country" placeholder="Country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
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

    function openNameEmailPopup(orderNumber) {
        document.getElementById('edit_order_number').value = orderNumber;
        document.getElementById('edit_name').value  = document.getElementById('display-customer-name')?.textContent.trim()  || '';
        document.getElementById('edit_phone').value = document.getElementById('display-customer-phone')?.textContent.trim() || '';
        document.getElementById('edit_address_line1').value = document.getElementById('address1')?.textContent.trim() || '';
        document.getElementById('edit_address_line2').value = document.getElementById('address2')?.textContent.trim() || '';
        document.getElementById('edit_city').value = document.getElementById('city')?.textContent.trim() || '';
        document.getElementById('edit_zipcode').value = document.getElementById('zipcode')?.textContent.trim() || '';
        document.getElementById('edit_country').value = document.getElementById('country')?.textContent.trim() || '';
        document.getElementById('edit_billing_address_line1').value = document.getElementById('billing_address1')?.textContent.trim() || '';
        document.getElementById('edit_billing_address_line2').value = document.getElementById('billing_address2')?.textContent.trim() || '';
        document.getElementById('edit_billing_city').value = document.getElementById('billing_city_city')?.textContent.trim() || '';
        document.getElementById('edit_billing_zipcode').value = document.getElementById('billing_city_zip')?.textContent.trim() || '';
        document.getElementById('edit_billing_country').value = document.getElementById('billing_country')?.textContent.trim() || '';
        document.getElementById('nameEmailPopup').classList.remove('hidden');
    }

    function closeNameEmailPopup() {
        document.getElementById('nameEmailPopup').classList.add('hidden');
    }

    document.getElementById('nameEmailForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const orderNumber = document.getElementById('edit_order_number').value;
        const name  = document.getElementById('edit_name').value.trim();
        const phone = document.getElementById('edit_phone').value.trim();
        const address_line1 = document.getElementById('edit_address_line1').value.trim();
        const address_line2 = document.getElementById('edit_address_line2').value.trim();
        const city = document.getElementById('edit_city').value.trim();
        const zipcode = document.getElementById('edit_zipcode').value.trim();
        const country = document.getElementById('edit_country').value.trim();
        const billing_address_line1 = document.getElementById('edit_billing_address_line1').value.trim();
        const billing_address_line2 = document.getElementById('edit_billing_address_line2').value.trim();
        const billing_city = document.getElementById('edit_billing_city').value.trim();
        const billing_zipcode = document.getElementById('edit_billing_zipcode').value.trim();
        const billing_country = document.getElementById('edit_billing_country').value.trim();

        if (!name || !phone) {
            alert("All fields (Name, Email, Phone) are required.");
            return;
        }

        fetch('index.php?page=orders&action=update_name_email_ajax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_number=${encodeURIComponent(orderNumber)}&customer_name=${encodeURIComponent(name)}&customer_phone=${encodeURIComponent(phone)}&address_line1=${encodeURIComponent(address_line1)}&address_line2=${encodeURIComponent(address_line2)}&city=${encodeURIComponent(city)}&zipcode=${encodeURIComponent(zipcode)}&country=${encodeURIComponent(country)}&billing_address_line1=${encodeURIComponent(billing_address_line1)}&billing_address_line2=${encodeURIComponent(billing_address_line2)}&billing_city=${encodeURIComponent(billing_city)}&billing_zipcode=${encodeURIComponent(billing_zipcode)}&billing_country=${encodeURIComponent(billing_country)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('display-customer-name').textContent  = name;
                document.getElementById('display-customer-phone').textContent = phone;
                document.getElementById('address1').textContent = address_line1;
                document.getElementById('address2').textContent = address_line2;
                document.getElementById('city').textContent = city;
                document.getElementById('zipcode').textContent = zipcode;
                document.getElementById('country').textContent = country;
                document.getElementById('billing_address1').textContent = billing_address_line1;
                document.getElementById('billing_address2').textContent = billing_address_line2;
                document.getElementById('billing_city_city').textContent = billing_city;
                document.getElementById('billing_city_zip').textContent = billing_zipcode;
                document.getElementById('billing_country').textContent = billing_country;

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
    document.addEventListener('DOMContentLoaded', function () {
        const accordionTriggers = document.querySelectorAll('.accordion-trigger');
            accordionTriggers.forEach(trigger => {
                // Remove previous handler if stored to avoid duplicate handlers
                if (trigger.__accordionClick__) {
                    trigger.removeEventListener('click', trigger.__accordionClick__);
                }

                const handler = function () {
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
    });
</script>