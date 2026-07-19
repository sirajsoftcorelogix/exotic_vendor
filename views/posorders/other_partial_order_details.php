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
$displayOrderNumber = (string)($orderremarks['order_number'] ?? ($order[0]['order_number'] ?? ''));
$invoicePdfUrl = trim((string)($invoicePdfUrl ?? ''));
$invoiceDisplay = is_array($invoiceDisplay ?? null) ? $invoiceDisplay : null;
$canEditInvoiceNumber = !empty($canEditInvoiceNumber);
$invoiceStatus = strtolower(trim((string)($invoiceDisplay['status'] ?? '')));
$invoiceStatusBadgeClass = match ($invoiceStatus) {
    'final' => 'bg-green-100 text-green-700',
    'proforma' => 'bg-yellow-100 text-yellow-700',
    'draft' => 'bg-gray-100 text-gray-700',
    default => 'bg-slate-100 text-slate-700',
};
$invoiceNumberDisplay = (string)($invoiceDisplay['invoice_number'] ?? '');
$invoiceDateDisplay = !empty($invoiceDisplay['invoice_date'])
    ? date('d M Y', strtotime((string)$invoiceDisplay['invoice_date']))
    : '—';
$invoiceSubtotalDisplay = number_format((float)($invoiceDisplay['subtotal'] ?? 0), 2);
$invoiceTaxDisplay = number_format((float)($invoiceDisplay['tax_amount'] ?? 0), 2);
$invoiceSummaryRows = (is_array($invoiceDisplay) && is_array($invoiceDisplay['summary_rows'] ?? null))
    ? $invoiceDisplay['summary_rows']
    : [];
$invoiceGoodsInclDisplay = number_format((float)($invoiceDisplay['subtotal_goods_incl'] ?? 0), 2);
$invoiceGrandTotalDisplay = number_format((float)($invoiceDisplay['pdf_grand_total'] ?? $invoiceDisplay['grand_total'] ?? 0), 2);
$paymentSummary = is_array($paymentSummary ?? null) ? $paymentSummary : ['order_total' => 0, 'paid_total' => 0, 'pending' => 0, 'is_fully_paid' => false, 'payments' => []];
$paymentRows = is_array($paymentSummary['payments'] ?? null) ? $paymentSummary['payments'] : [];
$paymentOrderTotalDisplay = number_format((float)($paymentSummary['order_total'] ?? 0), 2);
$paymentPaidTotalDisplay = number_format((float)($paymentSummary['paid_total'] ?? 0), 2);
$paymentPendingDisplay = number_format((float)($paymentSummary['pending'] ?? 0), 2);
$paymentIsFullyPaid = !empty($paymentSummary['is_fully_paid']);
$paymentsListUrl = base_url('?page=payments&action=list&order_number=' . rawurlencode($displayOrderNumber) . '&order_exact=1');
?>

<div class="min-h-screen bg-gray-50 p-6 font-sans text-black-900">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold"><?php echo htmlspecialchars($displayOrderNumber); ?></h1>
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
                        <?php if ($invoicePdfUrl !== ''): ?>
                            <a href="<?php echo htmlspecialchars($invoicePdfUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100">
                                Print Invoice
                            </a>
                        <?php else: ?>
                            <span class="flex items-center px-4 py-2 text-[13px] text-gray-400 cursor-not-allowed"
                                title="No invoice exists for this order yet.">
                                Print Invoice
                            </span>
                        <?php endif; ?>
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
                    ?>
                        <div class="flex items-center gap-4 accordion-trigger">
                            <input type="checkbox" class="h-5 w-5 rounded border-gray-300">
                            <div class="flex flex-1 items-start gap-5 rounded-2xl border border-gray-200 p-4">
                                <div class="h-32 w-32 flex-shrink-0 overflow-hidden rounded-xl border border-gray-100">
                                    <?php $imageUrl = (string)($item['image'] ?? ''); ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        class="h-full w-full object-cover cursor-pointer hover:opacity-90 transition-opacity pos-order-detail-enlarge"
                                        alt="product"
                                        title="Click to enlarge"
                                        data-full-image="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>">
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
                                            <div class="flex-shrink-0">
                                                <span class="rounded-full bg-green-600 px-3 py-1 text-[11px] font-semibold text-white whitespace-nowrap"><?php echo ucwords(str_replace('_', ' ', $item['status'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $linePricing = $linePricingByLineId[(int)($item['id'] ?? 0)] ?? null;
                                    if (is_array($linePricing)) {
                                        renderPartial('views/posorders/partials/line_item_pricing.php', [
                                            'linePricing' => $linePricing,
                                            'currencySymbol' => $currencysymbol,
                                        ]);
                                    }
                                    ?>
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
            </div>
        </div>
        <div class="space-y-6">
            <?php if ($invoiceDisplay !== null): ?>
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" id="order-invoice-details-card">
                    <div class="flex items-center justify-between gap-3 border-b border-orange-100 bg-gradient-to-r from-orange-50 to-white px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100 text-orange-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Tax Invoice</h3>
                                <p class="text-xs text-gray-500">Generated for this order</p>
                            </div>
                        </div>
                        <?php if ($invoiceStatus !== ''): ?>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $invoiceStatusBadgeClass; ?>">
                                <?php echo htmlspecialchars(ucfirst($invoiceStatus)); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Invoice Number</p>
                                <div class="mt-1 flex items-start justify-between gap-2">
                                    <p id="order-invoice-number-text" class="break-all font-mono text-sm font-semibold text-gray-900 leading-snug">
                                        <?php echo htmlspecialchars($invoiceNumberDisplay); ?>
                                    </p>
                                    <?php if ($canEditInvoiceNumber): ?>
                                        <button type="button"
                                            onclick="openInvoiceNumberEditPopup(<?php echo (int)$invoiceDisplay['id']; ?>, '<?php echo htmlspecialchars($invoiceNumberDisplay, ENT_QUOTES); ?>')"
                                            class="inline-flex shrink-0 items-center justify-center rounded-md p-1 text-gray-400 hover:bg-white hover:text-orange-600"
                                            title="Edit invoice number">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Invoice Date</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($invoiceDateDisplay); ?></p>
                            </div>
                        </div>

                        <?php if ($invoiceSummaryRows !== []): ?>
                            <?php renderPartial('views/posorders/partials/invoice_pdf_summary.php', [
                                'summaryRows' => $invoiceSummaryRows,
                                'currencySymbol' => '₹',
                            ]); ?>
                        <?php else: ?>
                            <div class="rounded-lg border border-gray-200 bg-white">
                                <div class="divide-y divide-gray-100 px-4 py-1 text-sm">
                                    <div class="flex items-center justify-between gap-4 py-2.5">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="tabular-nums font-medium text-gray-900">₹ <?php echo $invoiceSubtotalDisplay; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 py-2.5">
                                        <span class="text-gray-600">Tax</span>
                                        <span class="tabular-nums font-medium text-gray-900">₹ <?php echo $invoiceTaxDisplay; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-t border-gray-200 bg-gray-50 px-4 py-3 -mx-4 mt-1">
                                        <span class="text-sm font-bold text-gray-900">Net chargeable amount</span>
                                        <span class="text-base font-bold tabular-nums text-gray-900">₹ <?php echo $invoiceGrandTotalDisplay; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($invoicePdfUrl !== ''): ?>
                            <a href="<?php echo htmlspecialchars($invoicePdfUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 py-2.5 text-sm font-semibold text-orange-800 transition hover:border-orange-300 hover:bg-orange-100">
                                <svg width="16" height="16" viewBox="0 0 15 15" fill="none" aria-hidden="true">
                                    <path d="M2.62925 10.3889C1.64271 9.68768 1 8.54159 1 7.24672C1 5.47783 2.3 3.84375 4.25 3.52778C4.86168 2.07349 6.30934 1 7.99783 1C10.1607 1 11.9284 2.67737 12.05 4.79167C13.1978 5.29352 14 6.52522 14 7.85887C14 8.98648 13.4266 9.98004 12.5556 10.5634M7.5 14V6.77778M7.5 14L5.33333 11.8333M7.5 14L9.66667 11.8333"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Download / Print Invoice
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" id="order-payment-details-card">
                <div class="flex items-center justify-between gap-3 border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Payments</h3>
                            <p class="text-xs text-gray-500"><?php echo count($paymentRows); ?> payment<?php echo count($paymentRows) === 1 ? '' : 's'; ?> recorded</p>
                        </div>
                    </div>
                    <?php if ($paymentIsFullyPaid): ?>
                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Fully paid</span>
                    <?php elseif ((float)($paymentSummary['paid_total'] ?? 0) > 0): ?>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Partial</span>
                    <?php else: ?>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Unpaid</span>
                    <?php endif; ?>
                </div>

                <div class="space-y-4 p-5">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Order Total</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-gray-900">₹ <?php echo $paymentOrderTotalDisplay; ?></p>
                        </div>
                        <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 px-2 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Paid</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-emerald-800">₹ <?php echo $paymentPaidTotalDisplay; ?></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Pending</p>
                            <p class="mt-1 text-sm font-bold tabular-nums <?php echo (float)($paymentSummary['pending'] ?? 0) > 0.02 ? 'text-red-600' : 'text-gray-900'; ?>">₹ <?php echo $paymentPendingDisplay; ?></p>
                        </div>
                    </div>

                    <?php if ($paymentRows === []): ?>
                        <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                            No payments recorded for this order yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($paymentRows as $paymentRow):
                                $paymentId = (int)($paymentRow['id'] ?? 0);
                                $receiptLabel = trim((string)($paymentRow['receipt_number'] ?? ''));
                                if ($receiptLabel === '') {
                                    $receiptLabel = '#' . $paymentId;
                                }
                                $paymentDateRaw = trim((string)($paymentRow['payment_date'] ?? ''));
                                $paymentDateLabel = $paymentDateRaw !== ''
                                    ? date('d M Y', strtotime($paymentDateRaw))
                                    : '—';
                                $paymentAmount = number_format((float)($paymentRow['payment_amount'] ?? 0), 2);
                                $paymentMode = trim((string)($paymentRow['payment_mode'] ?? ''));
                                $paymentStage = trim((string)($paymentRow['payment_stage'] ?? ''));
                                $transactionId = trim((string)($paymentRow['transaction_id'] ?? ''));
                                $warehouseName = trim((string)($paymentRow['warehouse'] ?? ''));
                                $receiptUrl = base_url('?page=payments&action=receipt&id=' . $paymentId);
                            ?>
                                <div class="rounded-lg border border-gray-200 bg-white px-3 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="<?php echo htmlspecialchars($receiptUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-sm font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                                <?php echo htmlspecialchars($receiptLabel); ?>
                                            </a>
                                            <p class="mt-0.5 text-xs text-gray-500"><?php echo htmlspecialchars($paymentDateLabel); ?></p>
                                        </div>
                                        <p class="shrink-0 text-sm font-bold tabular-nums text-gray-900">₹ <?php echo $paymentAmount; ?></p>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <?php if ($paymentMode !== ''): ?>
                                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700"><?php echo htmlspecialchars($paymentMode); ?></span>
                                        <?php endif; ?>
                                        <?php if ($paymentStage !== ''): ?>
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium capitalize text-slate-700"><?php echo htmlspecialchars(str_replace('_', ' ', $paymentStage)); ?></span>
                                        <?php endif; ?>
                                        <?php if ($warehouseName !== ''): ?>
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"><?php echo htmlspecialchars($warehouseName); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($transactionId !== ''): ?>
                                        <p class="mt-2 truncate text-[11px] text-gray-500" title="<?php echo htmlspecialchars($transactionId, ENT_QUOTES); ?>">
                                            Txn: <?php echo htmlspecialchars($transactionId); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo htmlspecialchars($paymentsListUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100">
                        View all payments
                    </a>
                </div>
            </div>

            <!-- Note Section -->
            <div class="rounded-lg border bg-white p-5 shadow-sm relative" id="note-container-<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>">
                <textarea id="note-remarks-source" class="hidden" aria-hidden="true"><?php echo htmlspecialchars($orderremarks['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <button type="button"
                    id="note-edit-btn"
                    data-order-number="<?php echo htmlspecialchars($orderremarks['order_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    onclick="openNoteEditPopup()"
                    class="absolute top-4 right-4 text-black-500 hover:text-blue-600 transition-colors"
                    title="Edit Note">
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
<?php if ($canEditInvoiceNumber && $invoiceDisplay !== null): ?>
<div id="invoiceNumberEditPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6 relative">
        <button type="button" onclick="closeInvoiceNumberEditPopup()" class="absolute top-3 right-4 text-gray-500 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Invoice Number</h2>

        <form id="invoiceNumberEditForm">
            <input type="hidden" id="edit_invoice_id" name="invoice_id">

            <label class="block text-sm font-medium text-gray-700 mb-1" for="new_invoice_number">Invoice number</label>
            <input type="text" id="new_invoice_number" name="new_invoice_number"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeInvoiceNumberEditPopup()"
                    class="rounded-full px-5 py-2.5 bg-gray-200 text-gray-800 hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-full bg-[#D46B08] px-10 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-700">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<script>
    function openInvoiceNumberEditPopup(invoiceId, currentNumber) {
        document.getElementById('edit_invoice_id').value = invoiceId;
        document.getElementById('new_invoice_number').value = currentNumber || '';
        document.getElementById('invoiceNumberEditPopup').classList.remove('hidden');
        document.getElementById('new_invoice_number').focus();
        document.getElementById('new_invoice_number').select();
    }

    function closeInvoiceNumberEditPopup() {
        document.getElementById('invoiceNumberEditPopup')?.classList.add('hidden');
    }

    document.getElementById('invoiceNumberEditForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const invoiceId = document.getElementById('edit_invoice_id').value.trim();
        const newInvoiceNumber = document.getElementById('new_invoice_number').value.trim();

        if (!invoiceId || !newInvoiceNumber) {
            alert('Invoice number is required.');
            return;
        }

        const formData = new FormData();
        formData.append('invoice_id', invoiceId);
        formData.append('new_invoice_number', newInvoiceNumber);

        fetch('index.php?page=posinvoice&action=update_invoice_number_ajax', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Could not update invoice number.');
                    return;
                }

                closeInvoiceNumberEditPopup();

                const text = document.getElementById('order-invoice-number-text');
                const updated = data.invoice_number || newInvoiceNumber;
                if (text) {
                    text.textContent = updated;
                }
            })
            .catch(() => alert('Request failed. Please try again.'));
    });

    function openNoteEditPopup() {
        const btn = document.getElementById('note-edit-btn');
        const orderNumber = btn ? (btn.dataset.orderNumber || '') : '';
        const src = document.getElementById('note-remarks-source');
        const currentRemarks = src ? src.value : '';

        document.getElementById('note_order_number').value = orderNumber;
        document.getElementById('note_remarks').value = currentRemarks;
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

        fetch('index.php?page=posorders&action=update_note_ajax', {
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

                    const remarksSource = document.getElementById('note-remarks-source');
                    if (remarksSource) {
                        remarksSource.value = remarks;
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
        document.getElementById('edit_name').value = document.getElementById('display-customer-name')?.textContent.trim() || '';
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
        const name = document.getElementById('edit_name').value.trim();
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

        fetch('index.php?page=posorders&action=update_name_email_ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_number=${encodeURIComponent(orderNumber)}&customer_name=${encodeURIComponent(name)}&customer_phone=${encodeURIComponent(phone)}&address_line1=${encodeURIComponent(address_line1)}&address_line2=${encodeURIComponent(address_line2)}&city=${encodeURIComponent(city)}&zipcode=${encodeURIComponent(zipcode)}&country=${encodeURIComponent(country)}&billing_address_line1=${encodeURIComponent(billing_address_line1)}&billing_address_line2=${encodeURIComponent(billing_address_line2)}&billing_city=${encodeURIComponent(billing_city)}&billing_zipcode=${encodeURIComponent(billing_zipcode)}&billing_country=${encodeURIComponent(billing_country)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('display-customer-name').textContent = name;
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
    });

    function openImagePopup(imageUrl) {
        const popup = document.getElementById('imagePopup');
        const img = document.getElementById('popupImage');
        if (!popup || !img || !imageUrl) {
            return;
        }
        img.src = imageUrl;
        popup.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeImagePopup() {
        const popup = document.getElementById('imagePopup');
        const img = document.getElementById('popupImage');
        if (popup) {
            popup.classList.add('hidden');
        }
        if (img) {
            img.src = '';
        }
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImagePopup();
        }
    });

    document.addEventListener('click', function(e) {
        const thumb = e.target.closest('.pos-order-detail-enlarge');
        if (!thumb) {
            return;
        }
        e.stopPropagation();
        const imageUrl = thumb.getAttribute('data-full-image') || thumb.getAttribute('src') || '';
        if (imageUrl) {
            openImagePopup(imageUrl);
        }
    });
</script>
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[100]" onclick="closeImagePopup()">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button type="button" onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm" aria-label="Close">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>