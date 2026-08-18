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
    $lineId = (int)($item['id'] ?? 0);
    $linePricingRow = ($linePricingByLineId ?? [])[$lineId] ?? null;
    if (is_array($linePricingRow)) {
        $total_price += (float)($linePricingRow['chargeable_value'] ?? 0);
    } else {
        $total_price += (float)($item['finalprice'] ?? 0) * (int)($item['quantity'] ?? 1);
    }
endforeach;
$orderremarks = is_array($orderremarks ?? null) ? $orderremarks : [];
$customerdetails = is_array($customerdetails ?? null) ? $customerdetails : [];
$statusList = is_array($statusList ?? null) ? $statusList : [];
$order_status_list = is_array($order_status_list ?? null) ? $order_status_list : [];
$staff_list = is_array($staff_list ?? null) ? $staff_list : [];
$showOrderVendorName = (bool)($showOrderVendorName ?? false);
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
$countries = country_array();
$resolveCountryLabel = static function (?string $code) use ($countries): string {
    $code = trim((string)$code);
    if ($code === '') {
        return '';
    }
    return (string)($countries[$code] ?? $code);
};
$displayOrderNumber = (string)($orderremarks['order_number'] ?? ($order[0]['order_number'] ?? ''));
$invoicePdfUrl = trim((string)($invoicePdfUrl ?? ''));
$invoiceDisplay = is_array($invoiceDisplay ?? null) ? $invoiceDisplay : null;
$orderCurrencyCode = strtoupper(trim((string)($order[0]['currency'] ?? ($invoiceDisplay['currency'] ?? 'INR'))));
if ($orderCurrencyCode === '') {
    $orderCurrencyCode = 'INR';
}
$orderCurrencySymbol = vendor_currency_symbol($orderCurrencyCode);
$canEditInvoiceNumber = !empty($canEditInvoiceNumber);
$canEditOrderPrices = !empty($canEditOrderPrices);
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
    : 'â€”';
$invoiceSubtotalDisplay = number_format((float)($invoiceDisplay['subtotal'] ?? 0), 2);
$invoiceTaxDisplay = number_format((float)($invoiceDisplay['tax_amount'] ?? 0), 2);
$invoiceSummaryRows = (is_array($invoiceDisplay) && is_array($invoiceDisplay['summary_rows'] ?? null))
    ? $invoiceDisplay['summary_rows']
    : [];
$invoiceGoodsInclDisplay = number_format((float)($invoiceDisplay['subtotal_goods_incl'] ?? 0), 2);
$invoiceGrandTotalDisplay = number_format((float)($invoiceDisplay['pdf_grand_total'] ?? $invoiceDisplay['grand_total'] ?? 0), 2);
$paymentSummary = is_array($paymentSummary ?? null) ? $paymentSummary : ['order_total' => 0, 'paid_total' => 0, 'pending' => 0, 'is_fully_paid' => false, 'payments' => []];
$paymentRows = is_array($paymentSummary['payments'] ?? null) ? $paymentSummary['payments'] : [];
$creditAmount = (float)($paymentSummary['credit_amount'] ?? 0);
$creditAmountDisplay = number_format($creditAmount, 2);
$paymentOrderTotalDisplay = number_format((float)($paymentSummary['order_total'] ?? 0), 2);
$paymentPaidTotalDisplay = number_format((float)($paymentSummary['paid_total'] ?? 0), 2);
$paymentPendingDisplay = number_format((float)($paymentSummary['pending'] ?? 0), 2);
$paymentIsFullyPaid = !empty($paymentSummary['is_fully_paid']);
$paymentPendingAmount = (float)($paymentSummary['pending'] ?? 0);
$canAddOrderPayment = $paymentPendingAmount > 0.02;
$canCreateFinalInvoice = !empty($canCreateFinalInvoice);
$canPublishExoticSync = !empty($canPublishExoticSync);
$hasExoticSyncPayload = !empty($hasExoticSyncPayload);
$canFetchOrderJson = !empty($canFetchOrderJson);
$paymentsListUrl = base_url('?page=payments&action=list&order_number=' . rawurlencode($displayOrderNumber) . '&order_exact=1');
$salesReturnUrl = base_url('?page=sales_returns&action=create&order_number=' . rawurlencode($displayOrderNumber));
$invoiceIdForReturn = is_array($invoiceDisplay) ? (int)($invoiceDisplay['id'] ?? 0) : 0;
if ($invoiceIdForReturn <= 0) {
    $invoiceIdForReturn = (int)($order[0]['invoice_id'] ?? 0);
}
if ($invoiceIdForReturn > 0) {
    $salesReturnUrl .= '&invoice_id=' . $invoiceIdForReturn;
}
$salesReturnEligibility = is_array($salesReturnEligibility ?? null) ? $salesReturnEligibility : [];
$canCreateSalesReturn = !empty($salesReturnEligibility['can_create']);
$salesReturnDisabledReason = trim((string)($salesReturnEligibility['disabled_reason'] ?? ''));
if ($salesReturnDisabledReason === '' && !$canCreateSalesReturn) {
    $salesReturnDisabledReason = 'Sales return is not available for this order.';
}
$latestSalesReturnViewUrl = (int)($salesReturnEligibility['latest_return_id'] ?? 0) > 0
    ? base_url('?page=sales_returns&action=view&id=' . (int) $salesReturnEligibility['latest_return_id'])
    : '';
$canFollowUpOrder = !empty($canFollowUpOrder);
$followUpLinks = is_array($followUpLinks ?? null) ? $followUpLinks : ['outbound' => [], 'inbound' => null];
$followUpEligibility = is_array($followUpEligibility ?? null) ? $followUpEligibility : [];
$orderStatusPage = in_array(trim((string)($orderStatusPage ?? '')), ['orders', 'posorders'], true)
    ? trim((string) $orderStatusPage)
    : 'posorders';
$followUpReshipEligible = !empty($followUpEligibility['reship']['can_start']);
$followUpReplaceEligible = !empty($followUpEligibility['replace']['can_start']);
$followUpCopyEligible = !empty($followUpEligibility['copy']['can_start']);
$followUpReshipReason = trim((string)($followUpEligibility['reship']['disabled_reason'] ?? ''));
$followUpReplaceReason = trim((string)($followUpEligibility['replace']['disabled_reason'] ?? ''));
$followUpInboundLink = is_array($followUpLinks['inbound'] ?? null) ? $followUpLinks['inbound'] : null;
$followUpOutboundLinks = is_array($followUpLinks['outbound'] ?? null) ? $followUpLinks['outbound'] : [];
$followUpFlash = $_SESSION['order_follow_up_flash'] ?? null;
unset($_SESSION['order_follow_up_flash']);
require_once dirname(__DIR__, 2) . '/helpers/order_follow_up.php';
$proformaPrintUrl = trim((string)($proformaPrintUrl ?? ''));
$canPrintProforma = !empty($canPrintProforma);
$canPrintTaxInvoice = $invoicePdfUrl !== '' && $invoiceStatus === 'final';
$proformaPrintDisabledReason = $canPrintProforma
    ? ''
    : ($paymentIsFullyPaid
        ? 'Order is fully paid; use Print Invoice.'
        : ($invoiceStatus === 'final'
            ? 'This order has a final tax invoice.'
            : 'Proforma is available when payment is pending.'));
?>

<div class="min-h-screen bg-gray-50 p-6 font-sans text-gray-900">
    <!-- Order quick jump (order detail) -->
    <div class="mb-6 bg-gradient-to-r from-amber-50 via-white to-orange-50/50 rounded-xl border border-amber-100/80 shadow-sm p-4 sm:p-5">
        <form id="orderDetailSearchForm" class="relative" autocomplete="off">
            <label for="orderDetailSearchInput" class="block text-sm font-semibold text-gray-800 mb-1.5">
                <i class="fas fa-search text-amber-600 mr-1.5" aria-hidden="true"></i>Jump to order by Order Number
            </label>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-3">
                <div class="relative flex-1 min-w-0">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-amber-500/90 z-10">
                        <i class="fas fa-shopping-bag text-sm"></i>
                    </span>
                    <input type="text" id="orderDetailSearchInput" name="order_jump"
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg shadow-inner bg-white/90 placeholder:text-gray-400 focus:ring-2 focus:ring-amber-400/80 focus:border-amber-500 outline-none transition"
                        placeholder="Type Order Number — suggestions appear after 2 characters"
                        value="<?php echo htmlspecialchars($displayOrderNumber, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-autocomplete="list" aria-controls="orderDetailSearchSuggestions" aria-expanded="false" />
                    <div id="orderDetailSearchSuggestions" role="listbox"
                        class="hidden absolute left-0 right-0 top-full mt-1.5 max-h-72 overflow-y-auto rounded-xl border border-gray-200/90 bg-white shadow-xl shadow-amber-900/10 z-[100] py-1">
                    </div>
                </div>
                <button type="submit"
                    class="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-b from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-sm font-semibold shadow-md shadow-amber-600/25 border border-amber-600/30 transition w-full sm:w-auto">
                    <i class="fas fa-arrow-right text-xs opacity-90"></i> Go
                </button>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">Select a suggestion or enter the full Order Number and press <kbd class="px-1 rounded bg-gray-100 border text-[10px]">Go</kbd></p>
            <p id="orderDetailSearchError" class="hidden mt-3 text-sm font-medium text-red-600 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i><span id="orderDetailSearchErrorText"></span>
            </p>
        </form>
    </div>
    <script>
    (function () {
        var searchBase = <?php echo json_encode(base_url('?page=' . $orderStatusPage . '&action=search_orders'), JSON_UNESCAPED_SLASHES); ?>;
        var detailBase = <?php echo json_encode(base_url('?page=' . $orderStatusPage . '&action=get_order_details_html&type=outer&order_number='), JSON_UNESCAPED_SLASHES); ?>;
        var currentOrderNumber = <?php echo json_encode($displayOrderNumber, JSON_UNESCAPED_SLASHES); ?>;

        var form = document.getElementById('orderDetailSearchForm');
        var input = document.getElementById('orderDetailSearchInput');
        var box = document.getElementById('orderDetailSearchSuggestions');
        var errWrap = document.getElementById('orderDetailSearchError');
        var errText = document.getElementById('orderDetailSearchErrorText');
        var debounceTimer = null;
        var activeFetch = 0;
        var suggestAbort = null;

        function hideError() {
            if (errWrap) errWrap.classList.add('hidden');
            if (errText) errText.textContent = '';
        }

        function showError(msg) {
            if (errText) errText.textContent = msg;
            if (errWrap) errWrap.classList.remove('hidden');
        }

        function closeSuggestions() {
            if (box) {
                box.classList.add('hidden');
                box.innerHTML = '';
            }
            if (input) input.setAttribute('aria-expanded', 'false');
        }

        function goToOrder(orderNum) {
            orderNum = (orderNum || '').trim();
            if (!orderNum) return;
            if (orderNum.toLowerCase() === currentOrderNumber.toLowerCase()) {
                showError('You are already viewing order #' + currentOrderNumber + '.');
                return;
            }
            window.location.href = detailBase + encodeURIComponent(orderNum);
        }

        function escapeHtml(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function renderSuggestions(orders) {
            if (!box) return;
            box.innerHTML = '';
            if (!orders || !orders.length) {
                closeSuggestions();
                return;
            }
            if (input) input.setAttribute('aria-expanded', 'true');
            box.classList.remove('hidden');
            orders.forEach(function (ord) {
                var num = (ord.order_number != null ? String(ord.order_number) : '');
                var name = (ord.customer_name != null ? String(ord.customer_name) : '');
                var dt = (ord.date_added != null ? String(ord.date_added) : '');
                var status = (ord.status != null ? String(ord.status) : '');

                var meta = [];
                if (name) meta.push(name);
                if (dt) meta.push(dt);
                if (status) meta.push(status);

                var row = document.createElement('button');
                row.type = 'button';
                row.setAttribute('role', 'option');
                row.className = 'w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50/90 focus:bg-amber-50 outline-none border-b border-gray-50 last:border-0 flex flex-col gap-0.5 transition';
                row.innerHTML = '<span class="font-semibold text-gray-900 tracking-tight">#' + escapeHtml(num) + '</span>' +
                    (meta.length ? '<span class="text-xs text-gray-500">' + escapeHtml(meta.join(' · ')) + '</span>' : '');
                row.addEventListener('click', function () {
                    if (input) input.value = num;
                    closeSuggestions();
                    goToOrder(num);
                });
                box.appendChild(row);
            });
        }

        function fetchSuggestions(q) {
            if (q.length < 2) {
                closeSuggestions();
                return;
            }
            var myId = ++activeFetch;
            if (suggestAbort) {
                try { suggestAbort.abort(); } catch (e) {}
            }
            suggestAbort = (typeof AbortController !== 'undefined') ? new AbortController() : null;
            var url = searchBase + (searchBase.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q);
            var opts = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
            if (suggestAbort) opts.signal = suggestAbort.signal;
            fetch(url, opts)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (myId !== activeFetch) return;
                    if (data.success && data.orders && data.orders.length) {
                        renderSuggestions(data.orders);
                    } else {
                        closeSuggestions();
                    }
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') return;
                    if (myId !== activeFetch) return;
                    closeSuggestions();
                });
        }

        if (input) {
            input.addEventListener('input', function () {
                hideError();
                var q = (input.value || '').trim();
                clearTimeout(debounceTimer);
                if (q.length < 2) {
                    if (suggestAbort) {
                        try { suggestAbort.abort(); } catch (e) {}
                    }
                    closeSuggestions();
                    return;
                }
                debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 200);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeSuggestions();
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                hideError();
                var q = (input ? input.value : '').trim();
                if (!q) {
                    showError('Please enter an Order Number.');
                    return;
                }
                if (q.toLowerCase() === currentOrderNumber.toLowerCase()) {
                    showError('You are already viewing order #' + currentOrderNumber + '.');
                    return;
                }

                var checkUrl = searchBase + (searchBase.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q) + '&exact=1';
                fetch(checkUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success && data.order_number) {
                            goToOrder(data.order_number);
                        } else {
                            showError(data.message || ('Order #' + q + ' not found.'));
                        }
                    })
                    .catch(function () {
                        goToOrder(q);
                    });
            });
        }

        document.addEventListener('click', function (e) {
            if (form && !form.contains(e.target)) {
                closeSuggestions();
            }
        });
    })();
    </script>
    <?php if (is_array($followUpFlash) && trim((string)($followUpFlash['text'] ?? '')) !== ''): ?>
        <?php
        $flashType = strtolower(trim((string)($followUpFlash['type'] ?? 'info')));
        $flashClass = match ($flashType) {
            'error' => 'border-red-300 bg-red-50 text-red-950',
            'success' => 'border-green-300 bg-green-50 text-green-950',
            default => 'border-indigo-300 bg-indigo-50 text-indigo-950',
        };
        ?>
        <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($flashClass, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string) $followUpFlash['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (is_array($followUpInboundLink)): ?>
        <?php
        $sourceOrderNumber = trim((string)($followUpInboundLink['source_order_number'] ?? ''));
        $followUpTypeLabel = order_follow_up_type_label((string)($followUpInboundLink['follow_up_type'] ?? ''));
        $sourceOrderUrl = $sourceOrderNumber !== ''
            ? order_follow_up_order_details_url($sourceOrderNumber, $orderStatusPage)
            : '';
        ?>
        <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-950">
            <p class="font-semibold"><?= htmlspecialchars($followUpTypeLabel, ENT_QUOTES, 'UTF-8') ?> order</p>
            <p class="mt-1 text-xs text-indigo-900/90">
                Created from
                <?php if ($sourceOrderUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($sourceOrderUrl, ENT_QUOTES, 'UTF-8') ?>" class="font-medium underline hover:text-indigo-700">
                        #<?= htmlspecialchars($sourceOrderNumber, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    #<?= htmlspecialchars($sourceOrderNumber, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                · Pricing: <?= htmlspecialchars(order_follow_up_pricing_mode_label((string)($followUpInboundLink['pricing_mode'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    <?php endif; ?>
    <?php if ($followUpOutboundLinks !== []): ?>
        <div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800">
            <p class="font-semibold">Follow-up orders</p>
            <ul class="mt-2 space-y-1 text-xs">
                <?php foreach ($followUpOutboundLinks as $followUpRow): ?>
                    <?php if (!is_array($followUpRow)) { continue; } ?>
                    <?php
                    $childOrderNumber = trim((string)($followUpRow['follow_up_order_number'] ?? ''));
                    if ($childOrderNumber === '') { continue; }
                    $childOrderUrl = order_follow_up_order_details_url($childOrderNumber, $orderStatusPage);
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($childOrderUrl, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-indigo-700 underline hover:text-indigo-900">
                            #<?= htmlspecialchars($childOrderNumber, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        · <?= htmlspecialchars(order_follow_up_type_label((string)($followUpRow['follow_up_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        · <?= htmlspecialchars(order_follow_up_pricing_mode_label((string)($followUpRow['pricing_mode'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($canPublishExoticSync): ?>
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Local temp order â€” not on Exotic website yet</p>
                    <p class="mt-1 text-xs text-amber-900/90">
                        Use the publish icon next to the order number to send this order to Exotic.
                        <?php if (!$hasExoticSyncPayload): ?>
                            Saved checkout data is unavailable; items will be re-added to the Exotic cart automatically before order create.
                        <?php else: ?>
                            If the website cart expired, publish will rebuild the cart from these order lines and retry.
                        <?php endif; ?>
                    </p>
                </div>
                <button type="button"
                    id="publish_exotic_sync_btn"
                    onclick="publishExoticSyncOrder()"
                    class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">
                    Publish to Exotic
                </button>
            </div>
            <p id="publish_exotic_sync_error" class="mt-2 hidden text-xs text-red-700"></p>
            <p id="publish_exotic_sync_success" class="mt-2 hidden text-xs text-green-800"></p>
        </div>
    <?php endif; ?>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold"><?php echo htmlspecialchars($displayOrderNumber); ?></h1>
            <?php if ($canPublishExoticSync): ?>
                <button type="button"
                    id="publish_exotic_sync_icon_btn"
                    onclick="publishExoticSyncOrder()"
                    title="Publish to Exotic website (rebuilds cart if expired)"
                    aria-label="Publish to Exotic website"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-50 text-amber-800 transition hover:bg-amber-100 hover:text-amber-950 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 17v1a2 2 0 002 2h12a2 2 0 002-2v-1" />
                    </svg>
                </button>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-amber-900">Local temp</span>
            <?php endif; ?>
            <!-- <span class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white">Paid</span>
            <span class="rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-white">Canceled</span>
            <span class="rounded-full bg-yellow-500 px-3 py-1 text-xs font-semibold text-white">Refunded</span>
            <span class="rounded-full bg-gray-400 px-3 py-1 text-xs font-semibold text-white">Unfulfilled</span>
            <span class="rounded-full bg-orange-600 px-3 py-1 text-xs font-semibold text-white">Fulfilled</span>
            <span class="rounded-full bg-black px-3 py-1 text-xs font-semibold text-white">Archived</span> -->
        </div>

        <div class="flex items-center gap-2">
            <button class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">Restock</button>
            <?php if ($canCreateSalesReturn): ?>
                <button type="button"
                    data-sales-return-create
                    data-sales-return-url="<?= htmlspecialchars($salesReturnUrl, ENT_QUOTES, 'UTF-8') ?>"
                    data-order-number="<?= htmlspecialchars($displayOrderNumber, ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">
                    Return
                </button>
            <?php elseif ($latestSalesReturnViewUrl !== ''): ?>
                <a href="<?= htmlspecialchars($latestSalesReturnViewUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50">
                    View Return
                </a>
            <?php else: ?>
                <button type="button"
                    disabled
                    title="<?= htmlspecialchars($salesReturnDisabledReason, ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded border border-gray-200 bg-gray-100 px-4 py-1.5 text-sm font-medium text-gray-400 cursor-not-allowed">
                    Return
                </button>
            <?php endif; ?>
            <?php if ($canFollowUpOrder): ?>
                <div class="relative inline-block text-left">
                    <input type="checkbox" id="follow-up-dropdown-toggle" class="peer hidden">
                    <label for="follow-up-dropdown-toggle" class="flex cursor-pointer items-center gap-2 rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50 transition-colors select-none">
                        Follow-up
                        <svg class="w-4 h-4 transition-transform duration-200 peer-checked:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </label>
                    <div class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden opacity-0 invisible scale-95 transition-all duration-200 peer-checked:opacity-100 peer-checked:visible peer-checked:scale-100">
                        <div class="py-1">
                            <?php if ($followUpReshipEligible): ?>
                                <button type="button" data-open-follow-up="reship" class="flex w-full items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100 text-left">Reship (waived)</button>
                            <?php else: ?>
                                <span class="flex items-center px-4 py-2 text-[13px] text-gray-400 cursor-not-allowed" title="<?= htmlspecialchars($followUpReshipReason, ENT_QUOTES, 'UTF-8') ?>">Reship</span>
                            <?php endif; ?>
                            <?php if ($followUpReplaceEligible): ?>
                                <button type="button" data-open-follow-up="replace" class="flex w-full items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100 border-t border-gray-50 text-left">Replace (same price)</button>
                            <?php else: ?>
                                <span class="flex items-center px-4 py-2 text-[13px] text-gray-400 cursor-not-allowed border-t border-gray-50" title="<?= htmlspecialchars($followUpReplaceReason, ENT_QUOTES, 'UTF-8') ?>">Replace</span>
                            <?php endif; ?>
                            <?php if ($followUpCopyEligible): ?>
                                <button type="button" data-open-follow-up="copy" class="flex w-full items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100 border-t border-gray-50 text-left">Copy order</button>
                            <?php else: ?>
                                <span class="flex items-center px-4 py-2 text-[13px] text-gray-400 cursor-not-allowed border-t border-gray-50">Copy order</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <label for="follow-up-dropdown-toggle" class="fixed inset-0 h-full w-full cursor-default hidden peer-checked:block z-40"></label>
                </div>
            <?php endif; ?>
            <?php if ($canEditOrderPrices): ?>
                <button type="button" onclick="openEditPricesModal()" class="rounded border bg-white px-4 py-1.5 text-sm font-medium hover:bg-gray-50 transition-colors">Edit</button>
            <?php else: ?>
                <button type="button" disabled title="Order price editing is currently disabled" class="rounded border bg-gray-100 text-gray-400 px-4 py-1.5 text-sm font-medium cursor-not-allowed">Edit</button>
            <?php endif; ?>
            <div class="relative inline-block text-left">
                <input type="checkbox" id="dropdown-toggle" class="peer hidden">
                <label for="dropdown-toggle" class="flex cursor-pointer items-center gap-2 rounded bg-black px-4 py-1.5 text-sm font-medium text-white hover:bg-gray-800 transition-colors select-none">
                    Print
                    <svg class="w-4 h-4 transition-transform duration-200 peer-checked:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </label>
                <div class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden opacity-0 invisible scale-95 transition-all duration-200 peer-checked:opacity-100 peer-checked:visible peer-checked:scale-100">
                    <div class="py-1">
                        <?php if ($canPrintProforma): ?>
                            <a href="<?php echo htmlspecialchars($proformaPrintUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100">
                                Print Proforma
                            </a>
                        <?php else: ?>
                            <span class="flex items-center px-4 py-2 text-[13px] text-gray-400 cursor-not-allowed"
                                title="<?php echo htmlspecialchars($proformaPrintDisabledReason, ENT_QUOTES, 'UTF-8'); ?>">
                                Print Proforma
                            </span>
                        <?php endif; ?>
                        <?php if ($canPrintTaxInvoice): ?>
                            <a href="<?php echo htmlspecialchars($invoicePdfUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100 border-t border-gray-50">
                                Print Invoice
                            </a>
                        <?php else: ?>
                            <span class="flex items-center px-4 py-2 text-[13px] text-gray-400 cursor-not-allowed border-t border-gray-50"
                                title="<?php echo $invoiceStatus === 'proforma' ? 'Tax invoice is available after payment in full.' : 'No invoice exists for this order yet.'; ?>">
                                Print Invoice
                            </span>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars(pos_order_print_url($displayOrderNumber), ENT_QUOTES, 'UTF-8'); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-100 border-t border-gray-50">
                            Print Order
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
                        $linePricing = ($linePricingByLineId ?? [])[(int)($item['id'] ?? 0)] ?? null;
                        $netLineAmount = is_array($linePricing)
                            ? (float)($linePricing['chargeable_value'] ?? 0)
                            : (float)($item['finalprice'] ?? 0) * (int)($item['quantity'] ?? 1);
                        $listLineAmount = is_array($linePricing)
                            ? (float)($linePricing['list_price_incl'] ?? 0)
                            : (float)($item['finalprice'] ?? 0) * (int)($item['quantity'] ?? 1);
                        $headlineLineAmount = $listLineAmount > 0 ? $listLineAmount : $netLineAmount;
                        $hasExtendedPricing = is_array($linePricing)
                            && (((float)($linePricing['addons_total'] ?? 0)) > 0.001 || ((float)($linePricing['custom_reduce'] ?? 0)) > 0.001);
                        $lineAddons = order_line_addons_for_display($item['addons'] ?? null);
                        $lineId = (int)($item['id'] ?? 0);
                        $lineStatus = (string)($item['status'] ?? '');
                        $lineStatusLabel = (string)($statusList[$lineStatus] ?? ucwords(str_replace('_', ' ', $lineStatus)));
                        $statusOrderPayload = $buildStatusOrderPayload($item);
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
                                                <span class="ml-2 text-gray-700"><?php echo htmlspecialchars(trim((string)($item['sku'] ?? '')) !== '' ? (string)$item['sku'] : 'â€”'); ?></span>
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
                                            <?php if ($lineAddons !== []): ?>
                                                <?php foreach ($lineAddons as $addonRow): ?>
                                                    <p>
                                                        <span class="inline-block font-bold text-black">Addon</span>
                                                        <span class="text-black">:</span>
                                                        <span class="ml-2 text-gray-700"><?php echo htmlspecialchars((string)($addonRow['name'] ?? '')); ?></span>
                                                        <span class="ml-2 tabular-nums text-gray-600"><?php echo $currencysymbol . number_format((float)($addonRow['price'] ?? 0), 2); ?></span>
                                                    </p>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center gap-12">
                                            <?php if ($hasExtendedPricing): ?>
                                                <div class="text-right text-[13px] text-gray-500">
                                                    <p class="text-[11px] uppercase tracking-wide text-gray-500">List price</p>
                                                    <p class="tabular-nums font-bold text-[14px] text-gray-900"><?php echo $currencysymbol . number_format($headlineLineAmount, 2); ?></p>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex items-center gap-2 text-[13px] text-gray-500">
                                                    <span><?php echo $currencysymbol; ?><?php echo $item['finalprice']; ?> x</span>
                                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700"><?php echo $item['quantity']; ?></span>
                                                </div>

                                                <div class="w-20 text-right text-[14px] font-bold text-gray-900 tabular-nums">
                                                    <?php echo $currencysymbol . number_format($netLineAmount, 2); ?>
                                                </div>
                                            <?php endif; ?>
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
                                    <?php
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
                    if ($billingFirstName === '' && $shippingFirstName !== '') {
                        $billingFirstName = $shippingFirstName;
                        if ($billingLastName === '') {
                            $billingLastName = $shippingLastName;
                        }
                    }
                    if ($shippingFirstName === '' && $billingFirstName !== '') {
                        $shippingFirstName = $billingFirstName;
                        if ($shippingLastName === '') {
                            $shippingLastName = $billingLastName;
                        }
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
                    if ($billingAddress1 === '' && $billingCity === '') {
                        $billingAddress1 = $shippingAddress1;
                        $billingAddress2 = $shippingAddress2;
                        $billingCity = $shippingCity;
                        $billingState = $shippingState;
                        $billingZipcode = $shippingZipcode;
                        $billingCountry = $shippingCountry !== '' ? $shippingCountry : 'IN';
                        if ($billingMobile === '') {
                            $billingMobile = $shippingMobile;
                        }
                        if ($billingGstin === '') {
                            $billingGstin = $shippingGstin;
                        }
                    }
                    if ($shippingCountry === '') {
                        $shippingCountry = $billingCountry !== '' ? $billingCountry : 'IN';
                    }
                    if ($billingCountry === '') {
                        $billingCountry = $shippingCountry !== '' ? $shippingCountry : 'IN';
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
                                'currencySymbol' => $orderCurrencySymbol,
                            ]); ?>
                        <?php else: ?>
                            <div class="rounded-lg border border-gray-200 bg-white">
                                <div class="divide-y divide-gray-100 px-4 py-1 text-sm">
                                    <div class="flex items-center justify-between gap-4 py-2.5">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="tabular-nums font-medium text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $invoiceSubtotalDisplay; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 py-2.5">
                                        <span class="text-gray-600">Tax</span>
                                        <span class="tabular-nums font-medium text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $invoiceTaxDisplay; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-t border-gray-200 bg-gray-50 px-4 py-3 -mx-4 mt-1">
                                        <span class="text-sm font-bold text-gray-900">Net chargeable amount</span>
                                        <span class="text-base font-bold tabular-nums text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $invoiceGrandTotalDisplay; ?></span>
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
                        <?php if ($canCreateFinalInvoice): ?>
                            <p id="order_create_invoice_error" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></p>
                            <button type="button"
                                id="order_create_invoice_btn"
                                onclick="createOrderFinalInvoice()"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-700">
                                <?php echo in_array($invoiceStatus, ['proforma', 'draft'], true) ? 'Finalize Invoice' : 'Create Invoice'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($canCreateFinalInvoice): ?>
                <div class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm" id="order-create-invoice-card">
                    <div class="border-b border-amber-100 bg-gradient-to-r from-amber-50 to-white px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Tax Invoice</h3>
                                <p class="text-xs text-gray-500">Payment received in full â€” invoice not generated yet</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 p-5">
                        <p class="text-sm text-gray-600">Create the final tax invoice for this order now.</p>
                        <p id="order_create_invoice_error" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></p>
                        <button type="button"
                            id="order_create_invoice_btn"
                            onclick="createOrderFinalInvoice()"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-700">
                            Create Invoice
                        </button>
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
                    <div class="flex items-center gap-2">
                        <?php if ($paymentIsFullyPaid): ?>
                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Fully paid</span>
                        <?php elseif ((float)($paymentSummary['paid_total'] ?? 0) > 0): ?>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Partial</span>
                        <?php else: ?>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Unpaid</span>
                        <?php endif; ?>
                        <?php if ($canAddOrderPayment): ?>
                            <button type="button"
                                onclick="openOrderAddPayment()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Payment
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Order Total</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $paymentOrderTotalDisplay; ?></p>
                        </div>
                        <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 px-2 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Paid</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-emerald-800"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $paymentPaidTotalDisplay; ?></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Pending</p>
                            <p class="mt-1 text-sm font-bold tabular-nums <?php echo (float)($paymentSummary['pending'] ?? 0) > 0.02 ? 'text-red-600' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $paymentPendingDisplay; ?></p>
                        </div>
                    </div>

                    <?php if ($paymentRows === [] && $creditAmount <= 0.001): ?>
                        <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                            No payments recorded for this order yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php if ($creditAmount > 0.001): ?>
                                <div class="rounded-lg border border-gray-200 bg-white px-3 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">Credit</p>
                                            <p class="mt-0.5 text-xs text-gray-500">Store credit applied to this order</p>
                                        </div>
                                        <p class="text-sm font-bold tabular-nums text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $creditAmountDisplay; ?></p>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700">Credit</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($paymentRows as $paymentRow):
                                $paymentId = (int)($paymentRow['id'] ?? 0);
                                $receiptLabel = trim((string)($paymentRow['receipt_number'] ?? ''));
                                if ($receiptLabel === '') {
                                    $receiptLabel = '#' . $paymentId;
                                }
                                $paymentDateRaw = trim((string)($paymentRow['payment_date'] ?? ''));
                                $paymentDateLabel = $paymentDateRaw !== ''
                                    ? date('d M Y', strtotime($paymentDateRaw))
                                    : 'â€”';
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
                                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                                            <p class="text-sm font-bold tabular-nums text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol, ENT_QUOTES, 'UTF-8'); ?> <?php echo $paymentAmount; ?></p>
                                            <button type="button"
                                                onclick="printOrderPaymentReceipt(<?php echo $paymentId; ?>)"
                                                class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Print Receipt
                                            </button>
                                        </div>
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

                    <div class="grid gap-2 <?php echo $canAddOrderPayment ? 'sm:grid-cols-2' : ''; ?>">
                        <?php if ($canAddOrderPayment): ?>
                            <button type="button"
                                onclick="openOrderAddPayment()"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Payment
                            </button>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($paymentsListUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100">
                            View all payments
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dispatches Section -->
            <?php
            $dispatchRecordsList = is_array($dispatchRecords ?? null) ? $dispatchRecords : [];
            ?>
            <?php if (!empty($dispatchRecordsList)): ?>
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" id="order-dispatches-card">
                    <div class="flex items-center justify-between gap-3 border-b border-blue-100 bg-gradient-to-r from-blue-50 to-white px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.243c0-.621-.504-1.125-1.125-1.125H3.375A1.125 1.125 0 002.25 7.33v6.92c0 .621.504 1.125 1.125 1.125h1.5m10.5-6.75h2.909c.407 0 .783.197 1.011.53l2.25 3.3" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Dispatches</h3>
                                <p class="text-xs text-gray-500"><?php echo count($dispatchRecordsList); ?> shipment<?php echo count($dispatchRecordsList) === 1 ? '' : 's'; ?> found</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                            <?php echo count($dispatchRecordsList); ?> Box<?php echo count($dispatchRecordsList) === 1 ? '' : 'es'; ?>
                        </span>
                    </div>

                    <div class="space-y-3 p-5">
                        <?php foreach ($dispatchRecordsList as $dispatch):
                            $dispatchId = (int)($dispatch['id'] ?? 0);
                            $boxNo = trim((string)($dispatch['box_no'] ?? '1'));
                            $courierName = trim((string)($dispatch['courier_name'] ?? 'Courier'));
                            if ($courierName === '') {
                                $courierName = 'Courier';
                            }
                            $awbCode = trim((string)($dispatch['awb_code'] ?? $dispatch['tracking_number'] ?? ''));
                            $shipmentStatus = strtolower(trim((string)($dispatch['shipment_status'] ?? '')));
                            $isCancelled = in_array($shipmentStatus, ['cancelled', 'cancellation requested', 'cancel'], true);
                            
                            $statusBadgeClass = match (true) {
                                $isCancelled => 'bg-red-100 text-red-700 border-red-200',
                                in_array($shipmentStatus, ['delivered', 'shipped'], true) => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                in_array($shipmentStatus, ['manifest_generated', 'in_transit', 'out_for_delivery', 'pickup_scheduled', 'dispatched'], true) => 'bg-blue-100 text-blue-700 border-blue-200',
                                default => 'bg-amber-100 text-amber-800 border-amber-200',
                            };
                            $statusLabel = $shipmentStatus !== '' ? ucwords(str_replace(['_', '-'], ' ', $shipmentStatus)) : 'Pending';

                            $dispatchDateRaw = trim((string)($dispatch['dispatch_date'] ?? $dispatch['created_at'] ?? ''));
                            $dispatchDateLabel = $dispatchDateRaw !== '' && $dispatchDateRaw !== '0000-00-00'
                                ? date('d M Y', strtotime($dispatchDateRaw))
                                : '—';

                            $labelUrl = trim((string)($dispatch['label_url'] ?? ''));
                            
                            $trackingUrl = trim((string)($dispatch['tracking_url'] ?? ''));
                            if ($trackingUrl === '' && $awbCode !== '') {
                                $trackingUrl = 'https://shiprocket.co/tracking/' . urlencode($awbCode);
                            }
                        ?>
                            <div class="rounded-lg border border-gray-200 bg-white p-3.5 shadow-2xs transition hover:border-gray-300">
                                <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-gray-900">Box #<?php echo htmlspecialchars($boxNo); ?></span>
                                            <span class="rounded border px-2 py-0.5 text-[10px] font-semibold <?php echo $statusBadgeClass; ?>">
                                                <?php echo htmlspecialchars($statusLabel); ?>
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-600">
                                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($courierName); ?></span>
                                            <?php if ($dispatchDateLabel !== '—'): ?>
                                                <span class="text-gray-400">•</span>
                                                <span><?php echo htmlspecialchars($dispatchDateLabel); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <?php if ($awbCode !== ''): ?>
                                        <div class="text-right">
                                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-gray-400">AWB / Tracking</span>
                                            <span class="text-xs font-mono font-semibold text-gray-800"><?php echo htmlspecialchars($awbCode); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php
                                $weight = (float)($dispatch['weight'] ?? 0);
                                $boxSize = trim((string)($dispatch['box_size'] ?? ''));
                                if ($weight > 0 || $boxSize !== ''):
                                ?>
                                    <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-gray-500">
                                        <?php if ($weight > 0): ?>
                                            <span>Weight: <strong class="text-gray-700"><?php echo $weight; ?> kg</strong></span>
                                        <?php endif; ?>
                                        <?php if ($boxSize !== ''): ?>
                                            <span>Size: <strong class="text-gray-700"><?php echo htmlspecialchars($boxSize); ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3 flex flex-wrap items-center gap-2 pt-1 border-t border-gray-50">
                                    <?php if ($labelUrl !== '' && !$isCancelled): ?>
                                        <a href="<?php echo htmlspecialchars($labelUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.6 0-1.104-.467-1.12-1.066L5.88 18m11.78 0H5.88m11.78 0l-.33-3.63a3 3 0 00-2.986-2.728H9.656a3 3 0 00-2.986 2.728L6.34 18M18 10.5a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                            </svg>
                                            Print Label
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($trackingUrl !== ''): ?>
                                        <a href="<?php echo htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors">
                                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                            Track Shipment
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!$isCancelled): ?>
                                        <button type="button"
                                            onclick="cancelSingleDispatch(<?php echo $dispatchId; ?>)"
                                            class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors ml-auto">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Cancel Dispatch
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Note Section -->
            <div class="rounded-lg border bg-white p-5 shadow-sm relative" id="note-container-<?= htmlspecialchars($orderremarks['order_number'] ?? '') ?>">
                <textarea id="note-remarks-source" class="hidden" aria-hidden="true"><?php echo htmlspecialchars($orderremarks['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <button type="button"
                    id="note-edit-btn"
                    data-order-number="<?php echo htmlspecialchars($orderremarks['order_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    onclick="openNoteEditPopup()"
                    class="absolute top-4 right-4 text-gray-500 hover:text-blue-600 transition-colors"
                    title="Edit Note">
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

            <?php if ($canFetchOrderJson): ?>
                <div class="flex justify-end">
                    <button type="button"
                        id="fetch_order_json_btn"
                        onclick="openOrderJsonModal()"
                        title="Fetch live JSON from Exotic vendor API (read-only)"
                        class="text-xs font-normal text-red-600 hover:text-red-700 underline-offset-2 hover:underline">
                        Exotic API JSON
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/../../helpers/pos_payment_receipt.php';
renderPartial('views/shared/partials/pos_payment_modal.php', [
    'posPaymentModalTitle' => 'Record payment',
    'posPaymentModalIntro' => 'Add one or more payment lines for the pending balance. Each row is saved under the same receipt.',
    'posPaymentModalSubmitLabel' => 'Confirm payment',
    'posPaymentModalSubmitId' => 'posOrderPaymentSubmitBtn',
    'posPaymentModalShowCustomInvoice' => false,
    'posPaymentModalShowApiDebug' => false,
]);
?>
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
                                <input type="text" id="edit_shipping_address_line1" name="shipping_address_line1" placeholder="Address Line 1" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <input type="text" id="edit_shipping_address_line2" name="shipping_address_line2" placeholder="Address Line 2" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" id="edit_shipping_city" name="shipping_city" placeholder="City" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" id="edit_shipping_zipcode" name="shipping_zipcode" placeholder="Zipcode" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <input type="text" id="edit_shipping_state" name="shipping_state" placeholder="State" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                <select id="edit_shipping_state_select" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500"></select>
                                <select id="edit_shipping_country" name="shipping_country" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
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
                                    <input type="text" id="edit_billing_last_name" name="last_name" placeholder="Last Name" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <input type="text" id="edit_billing_address_line1" name="address_line1" placeholder="Address Line 1 *" required class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
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
                                <input type="text" id="edit_billing_gstin" name="gstin" placeholder="GSTIN (PAN not required if provided)" maxlength="15" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white uppercase focus:ring-blue-500 focus:border-blue-500">
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

<?php if ($canFetchOrderJson): ?>
<div id="orderJsonModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[90] p-4" onclick="closeOrderJsonModal(event)">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col relative" onclick="event.stopPropagation();">
        <button type="button" onclick="closeOrderJsonModal()" class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-sm z-10">✕</button>
        <div class="p-5 border-b border-gray-200 pr-14">
            <h2 class="text-lg font-bold text-gray-900">Exotic vendor API JSON</h2>
            <p class="text-sm text-gray-600 mt-1">Live response from <code class="text-xs bg-gray-100 px-1 rounded">vendor-api/order/fetch</code> for order <strong id="orderJsonOrderLabel"><?php echo htmlspecialchars($displayOrderNumber, ENT_QUOTES, 'UTF-8'); ?></strong>. Read-only â€” does not update local data.</p>
        </div>
        <div class="p-5 overflow-y-auto flex-1 min-h-0">
            <div id="orderJsonLoading" class="hidden text-sm text-gray-600 mb-3">Fetching latest JSON from Exoticâ€¦</div>
            <div id="orderJsonError" class="hidden text-sm text-red-600 mb-3"></div>
            <div id="orderJsonMeta" class="hidden text-xs text-gray-500 mb-2"></div>
            <pre id="orderJsonPre" class="hidden text-xs leading-relaxed bg-gray-900 text-green-100 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words max-h-[60vh]"></pre>
        </div>
        <div class="p-5 border-t border-gray-200 flex justify-end gap-3">
            <button type="button" id="orderJsonCopyBtn" disabled onclick="copyOrderJson()" class="px-4 py-2 bg-gray-100 text-gray-800 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">Copy JSON</button>
            <button type="button" onclick="refetchOrderJson()" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Refetch</button>
            <button type="button" onclick="closeOrderJsonModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$orderStatusPage = in_array($orderStatusPage ?? '', ['orders', 'posorders'], true) ? $orderStatusPage : 'posorders';
renderPartial('views/shared/partials/order_status_update_popup.php', [
    'order_status_list' => $order_status_list ?? [],
    'staff_list' => $staff_list ?? [],
    'showOrderVendorName' => !empty($showOrderVendorName),
    'orderPage' => $orderStatusPage,
]);
?>

<script src="<?php echo base_url(); ?>assets/js/pos_payment_split.js"></script>
<?php if ($canFetchOrderJson): ?>
<script>
window.orderJsonModalConfig = {
    orderNumber: <?php echo json_encode($displayOrderNumber, JSON_UNESCAPED_UNICODE); ?>,
    fetchUrl: <?php echo json_encode(base_url('index.php?page=posorders&action=fetch_order_json'), JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="<?php echo base_url('assets/js/order_json_modal.js'); ?>"></script>
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
                            displayEl.innerHTML = '<em class="text-gray-400">No notes from customer</em>';
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
        const phoneVal = document.getElementById('display-customer-phone')?.textContent.trim() || document.getElementById('shipping_mobile')?.textContent.trim() || '';
        document.getElementById('edit_phone').value = phoneVal;

        const shipFirst = document.getElementById('shipping_first_name')?.textContent.trim() || '';
        const shipLast = document.getElementById('shipping_last_name')?.textContent.trim() || '';
        const billFirst = document.getElementById('billing_first_name')?.textContent.trim() || '';
        const billLast = document.getElementById('billing_last_name')?.textContent.trim() || '';

        const customerNameVal = document.getElementById('display-customer-name')?.textContent.trim() || '';
        const nameParts = customerNameVal ? customerNameVal.split(/\s+/) : [];
        const fallbackFirst = nameParts[0] || '';
        const fallbackLast = nameParts.slice(1).join(' ') || '';

        document.getElementById('edit_shipping_first_name').value = shipFirst || billFirst || fallbackFirst;
        document.getElementById('edit_shipping_last_name').value = shipLast || billLast || fallbackLast;
        document.getElementById('edit_billing_first_name').value = billFirst || shipFirst || fallbackFirst;
        document.getElementById('edit_billing_last_name').value = billLast || shipLast || fallbackLast;

        const shipAddr1 = document.getElementById('shipping_address1')?.textContent.trim() || '';
        const shipAddr2 = document.getElementById('shipping_address2')?.textContent.trim() || '';
        const shipCity = document.getElementById('shipping_city')?.textContent.trim() || '';
        const shipZip = document.getElementById('shipping_zipcode')?.textContent.trim() || '';
        const shipCountry = document.getElementById('shipping_country')?.dataset.code || 'IN';
        const shipGstin = document.getElementById('shipping_gstin')?.textContent.trim() || '';

        const billAddr1 = document.getElementById('billing_address1')?.textContent.trim() || '';
        const billAddr2 = document.getElementById('billing_address2')?.textContent.trim() || '';
        const billCity = document.getElementById('billing_city')?.textContent.trim() || '';
        const billZip = document.getElementById('billing_zipcode')?.textContent.trim() || '';
        const billCountry = document.getElementById('billing_country')?.dataset.code || 'IN';
        const billGstin = document.getElementById('billing_gstin')?.textContent.trim() || '';

        document.getElementById('edit_shipping_address_line1').value = shipAddr1 || billAddr1;
        document.getElementById('edit_shipping_address_line2').value = shipAddr2 || billAddr2;
        document.getElementById('edit_shipping_city').value = shipCity || billCity;
        document.getElementById('edit_shipping_zipcode').value = shipZip || billZip;
        document.getElementById('edit_shipping_country').value = shipCountry || billCountry || 'IN';
        document.getElementById('edit_shipping_gstin').value = shipGstin || billGstin;

        document.getElementById('edit_billing_address_line1').value = billAddr1 || shipAddr1;
        document.getElementById('edit_billing_address_line2').value = billAddr2 || shipAddr2;
        document.getElementById('edit_billing_city').value = billCity || shipCity;
        document.getElementById('edit_billing_zipcode').value = billZip || shipZip;
        document.getElementById('edit_billing_country').value = billCountry || shipCountry || 'IN';
        document.getElementById('edit_billing_gstin').value = billGstin || shipGstin;

        const shippingCity = shipCity || billCity;
        const shippingState = document.getElementById('shipping_state')?.textContent.trim() || document.getElementById('billing_state')?.textContent.trim() || '';

        const billingCity = billCity || shipCity;
        const billingState = document.getElementById('billing_state')?.textContent.trim() || document.getElementById('shipping_state')?.textContent.trim() || '';

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
        let first_name = document.getElementById('edit_billing_first_name').value.trim();
        let last_name = document.getElementById('edit_billing_last_name').value.trim();
        let shipping_first_name = document.getElementById('edit_shipping_first_name').value.trim();
        let shipping_last_name = document.getElementById('edit_shipping_last_name').value.trim();
        let phone = document.getElementById('edit_phone').value.trim();

        let address_line1 = document.getElementById('edit_billing_address_line1').value.trim();
        let address_line2 = document.getElementById('edit_billing_address_line2').value.trim();
        let city = document.getElementById('edit_billing_city').value.trim();
        let state = getOrderStateValue('billing');
        let zipcode = document.getElementById('edit_billing_zipcode').value.trim();
        let country = document.getElementById('edit_billing_country').value.trim();

        let shipping_address_line1 = document.getElementById('edit_shipping_address_line1').value.trim();
        let shipping_address_line2 = document.getElementById('edit_shipping_address_line2').value.trim();
        let shipping_city = document.getElementById('edit_shipping_city').value.trim();
        let shipping_state = getOrderStateValue('shipping');
        let shipping_zipcode = document.getElementById('edit_shipping_zipcode').value.trim();
        let shipping_country = document.getElementById('edit_shipping_country').value.trim();

        const gstin = document.getElementById('edit_billing_gstin').value.trim().toUpperCase();
        const shipping_gstin = document.getElementById('edit_shipping_gstin').value.trim().toUpperCase();

        if (!first_name && shipping_first_name) first_name = shipping_first_name;
        if (!shipping_first_name && first_name) shipping_first_name = first_name;
        if (!last_name && shipping_last_name) last_name = shipping_last_name;
        if (!shipping_last_name && last_name) shipping_last_name = last_name;

        if (!address_line1 && shipping_address_line1) address_line1 = shipping_address_line1;
        if (!shipping_address_line1 && address_line1) shipping_address_line1 = address_line1;
        if (!address_line2 && shipping_address_line2) address_line2 = shipping_address_line2;
        if (!shipping_address_line2 && address_line2) shipping_address_line2 = address_line2;
        if (!city && shipping_city) city = shipping_city;
        if (!shipping_city && city) shipping_city = city;
        if (!state && shipping_state) state = shipping_state;
        if (!shipping_state && state) shipping_state = state;
        if (!zipcode && shipping_zipcode) zipcode = shipping_zipcode;
        if (!shipping_zipcode && zipcode) shipping_zipcode = zipcode;
        if (!country && shipping_country) country = shipping_country;
        if (!shipping_country && country) shipping_country = country;

        const name = [first_name, last_name].filter(Boolean).join(' ');

        if (!first_name || !phone || !address_line1) {
            const valMsg = "Billing first name, phone and Address Line 1 are required.";
            if (typeof window.showPosMessageModal === 'function') {
                window.showPosMessageModal({ title: 'Validation Required', message: valMsg, tone: 'warning' });
            } else {
                alert(valMsg);
            }
            return;
        }

        const params = new URLSearchParams({
            order_number: orderNumber,
            customer_name: name,
            customer_phone: phone,
            first_name: first_name,
            last_name: last_name,
            shipping_first_name: shipping_first_name,
            shipping_last_name: shipping_last_name,
            address_line1: address_line1,
            address_line2: address_line2,
            city: city,
            state: state,
            zipcode: zipcode,
            country: country,
            gstin: gstin,
            billing_address_line1: shipping_address_line1,
            billing_address_line2: shipping_address_line2,
            billing_city: shipping_city,
            shipping_state: shipping_state,
            billing_zipcode: shipping_zipcode,
            billing_country: shipping_country,
            shipping_gstin: shipping_gstin
        });

        const fetchUrl = `index.php?page=${encodeURIComponent(<?php echo json_encode($orderStatusPage ?? 'posorders', JSON_UNESCAPED_SLASHES); ?>)}&action=update_name_email_ajax`;

        fetch(fetchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.showPosMessageModal === 'function') {
                        window.showPosMessageModal({
                            title: 'Success',
                            message: 'Customer information updated successfully!',
                            tone: 'success',
                            onClose: function() {
                                closeNameEmailPopup();
                                window.location.reload();
                            }
                        });
                    } else {
                        alert("Customer information updated successfully!");
                        closeNameEmailPopup();
                        window.location.reload();
                    }
                } else {
                    const errMsg = "Failed to save: " + (data.message || "Unknown error");
                    if (typeof window.showPosMessageModal === 'function') {
                        window.showPosMessageModal({ title: 'Error', message: errMsg, tone: 'error' });
                    } else {
                        alert(errMsg);
                    }
                }
            })
            .catch(() => {
                const connMsg = "Connection problem. Please try again.";
                if (typeof window.showPosMessageModal === 'function') {
                    window.showPosMessageModal({ title: 'Error', message: connMsg, tone: 'error' });
                } else {
                    alert(connMsg);
                }
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

    let orderPaymentState = {
        pending: <?php echo json_encode(round($paymentPendingAmount, 2)); ?>,
        orderTotal: <?php echo json_encode(round((float)($paymentSummary['order_total'] ?? 0), 2)); ?>,
        orderNumber: <?php echo json_encode($displayOrderNumber); ?>,
    };

    function printOrderPaymentReceipt(paymentId) {
        if (!paymentId) {
            return;
        }
        window.open('?page=payments&action=receipt&id=' + encodeURIComponent(String(paymentId)), '_blank');
    }

    function openOrderAddPayment() {
        fetch('?page=payments&action=get_payment_summary&order_number=' + encodeURIComponent(orderPaymentState.orderNumber))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    orderPaymentState.pending = parseFloat(data.pending) || 0;
                    orderPaymentState.orderTotal = parseFloat(data.order_total) || orderPaymentState.orderTotal;
                }
                if (orderPaymentState.pending <= 0.02) {
                    alert('This order has no pending balance to collect.');
                    return;
                }
                if (window.PosPaymentSplit) {
                    window.PosPaymentSplit.openModal(orderPaymentState.pending);
                }
            })
            .catch(function() {
                if (window.PosPaymentSplit) {
                    window.PosPaymentSplit.openModal(orderPaymentState.pending);
                }
            });
    }

    function saveOrderPaymentFromModal(payInfo) {
        var submitBtn = document.getElementById('posOrderPaymentSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        var formData = new FormData();
        formData.append('order_id', orderPaymentState.orderNumber);
        formData.append('payment_stage', payInfo.payment_stage || 'final');
        formData.append('note', payInfo.payment_note || '');
        formData.append('payment_date', payInfo.payment_date || '');
        (payInfo.payment_splits || []).forEach(function(split, idx) {
            formData.append('payment_splits[' + idx + '][mode]', split.mode);
            formData.append('payment_splits[' + idx + '][amount]', String(split.amount));
            formData.append('payment_splits[' + idx + '][transaction_id]', split.transaction_id || '');
        });

        fetch('index.php?page=payments&action=save_payment', {
            method: 'POST',
            body: formData,
        })
            .then(function(res) { return res.text(); })
            .then(function(text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error((text || '').trim().slice(0, 200) || 'Invalid server response');
                }
                if (!data.success) {
                    if (window.PosPaymentSplit) {
                        window.PosPaymentSplit.showSplitValidationError(data.message || 'Save failed');
                    }
                    return;
                }

                if (window.PosPaymentSplit) {
                    window.PosPaymentSplit.closeModal();
                }

                if (data.payment_id) {
                    printOrderPaymentReceipt(data.payment_id);
                }

                if (data.invoice_id) {
                    window.open('?page=posinvoice&action=generate_pdf&invoice_id=' + encodeURIComponent(String(data.invoice_id)), '_blank');
                } else if (data.invoice_message) {
                    alert(data.invoice_message);
                }

                setTimeout(function() {
                    window.location.reload();
                }, 400);
            })
            .catch(function(err) {
                if (window.PosPaymentSplit) {
                    window.PosPaymentSplit.showSplitValidationError(err.message || 'Could not save payment. Please try again.');
                }
            })
            .finally(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
    }

    function publishExoticSyncOrder() {
        var btn = document.getElementById('publish_exotic_sync_btn');
        var iconBtn = document.getElementById('publish_exotic_sync_icon_btn');
        var errEl = document.getElementById('publish_exotic_sync_error');
        var okEl = document.getElementById('publish_exotic_sync_success');
        if (errEl) {
            errEl.classList.add('hidden');
            errEl.textContent = '';
        }
        if (okEl) {
            okEl.classList.add('hidden');
            okEl.textContent = '';
        }
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Publishingâ€¦';
        }
        if (iconBtn) {
            iconBtn.disabled = true;
        }

        var formData = new FormData();
        formData.append('order_number', orderPaymentState.orderNumber);

        fetch('index.php?page=posorders&action=publish_exotic_sync', {
            method: 'POST',
            body: formData,
        })
            .then(function(res) { return res.text(); })
            .then(function(text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error((text || '').trim().slice(0, 200) || 'Invalid server response');
                }
                if (!data.success) {
                    var failMsg = data.message || 'Could not publish to Exotic.';
                    if (errEl) {
                        errEl.textContent = failMsg;
                        errEl.classList.remove('hidden');
                    } else {
                        alert(failMsg);
                    }
                    return;
                }

                var msg = data.message || 'Published to Exotic.';
                if (data.new_order_number && data.new_order_number !== orderPaymentState.orderNumber) {
                    window.location.href = '?page=posorders&action=get_order_details_html&type=outer&order_number='
                        + encodeURIComponent(data.new_order_number);
                    return;
                }

                if (okEl) {
                    okEl.textContent = msg;
                    okEl.classList.remove('hidden');
                } else {
                    alert(msg);
                }
                window.location.reload();
            })
            .catch(function(err) {
                if (errEl) {
                    errEl.textContent = err && err.message ? err.message : 'Publish request failed.';
                    errEl.classList.remove('hidden');
                } else {
                    alert(err && err.message ? err.message : 'Publish request failed.');
                }
            })
            .finally(function() {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Publish to Exotic';
                }
                if (iconBtn) {
                    iconBtn.disabled = false;
                }
            });
    }

    function createOrderFinalInvoice() {
        var btn = document.getElementById('order_create_invoice_btn');
        var errEl = document.getElementById('order_create_invoice_error');
        if (errEl) {
            errEl.classList.add('hidden');
            errEl.textContent = '';
        }
        if (btn) {
            btn.disabled = true;
        }

        var formData = new FormData();
        formData.append('order_number', orderPaymentState.orderNumber);

        fetch('index.php?page=posorders&action=create_invoice_from_order', {
            method: 'POST',
            body: formData,
        })
            .then(function(res) { return res.text(); })
            .then(function(text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error((text || '').trim().slice(0, 200) || 'Invalid server response');
                }
                if (!data.success) {
                    if (data.require_compliance && window.ComplianceDocModal) {
                        window.ComplianceDocModal.open({
                            customerId: data.customer_id,
                            message: data.message,
                            gstin: data.gstin || '',
                            pan: data.pan || '',
                            residencyStatus: data.residency_status || '',
                            onSuccess: function () {
                                createOrderFinalInvoice();
                            }
                        });
                        return;
                    }
                    if (errEl) {
                        errEl.textContent = data.message || 'Invoice could not be created.';
                        errEl.classList.remove('hidden');
                    } else if (typeof window.showPosMessageModal === 'function') {
                        window.showPosMessageModal({ title: 'Invoice', message: data.message || 'Invoice could not be created.', tone: 'error' });
                    }
                    return;
                }

                if (data.invoice_pdf_url) {
                    window.open(data.invoice_pdf_url, '_blank');
                } else if (data.invoice_id) {
                    window.open('?page=posinvoice&action=generate_pdf&invoice_id=' + encodeURIComponent(String(data.invoice_id)), '_blank');
                }

                setTimeout(function() {
                    window.location.reload();
                }, 400);
            })
            .catch(function(err) {
                if (errEl) {
                    errEl.textContent = err.message || 'Could not create invoice. Please try again.';
                    errEl.classList.remove('hidden');
                } else {
                    alert(err.message || 'Could not create invoice. Please try again.');
                }
            })
            .finally(function() {
                if (btn) {
                    btn.disabled = false;
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.PosPaymentSplit) {
            return;
        }
        window.PosPaymentSplit.init({
            modeOptions: <?php echo json_encode(pos_payment_mode_options_for_view(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            submitButtonId: 'posOrderPaymentSubmitBtn',
            getTargetTotal: function() { return orderPaymentState.pending; },
            getDisplayOrderTotal: function() { return orderPaymentState.orderTotal; },
            onSubmit: saveOrderPaymentFromModal,
        });
    });
</script>
<?php
if ($canFollowUpOrder) {
    require dirname(__DIR__) . '/shared/partials/order_follow_up_modal.php';
}
?>
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[100]" onclick="closeImagePopup()">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button type="button" onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm" aria-label="Close">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<!-- Cancel Dispatch Confirmation Modal -->
<div id="cancelDispatchModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl ring-1 ring-gray-200">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </span>
            <div>
                <h3 class="text-base font-bold text-gray-900">Cancel Dispatch</h3>
                <p class="text-xs text-gray-500">Confirm shipment cancellation</p>
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-600">
            Are you sure you want to cancel this dispatch? This will request cancellation with the courier.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button"
                onclick="closeCancelDispatchModal()"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Back
            </button>
            <button type="button"
                id="confirmCancelDispatchBtn"
                class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                Confirm Cancel
            </button>
        </div>
    </div>
</div>

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
                                    <label class="text-xs font-semibold text-gray-700">Unit Final Price (<?php echo htmlspecialchars($orderCurrencySymbol); ?>)</label>
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
                                        Line Total: <?php echo htmlspecialchars($orderCurrencySymbol); ?><span class="line-calc-total font-medium"><?php echo number_format($price * $qty, 2); ?></span>
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
                                -<?php echo htmlspecialchars($orderCurrencySymbol); ?><?php echo number_format($existingCouponReduce, 2); ?>
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
                                -<?php echo htmlspecialchars($orderCurrencySymbol); ?><?php echo number_format($existingGiftVoucherReduce, 2); ?>
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
                                -<?php echo htmlspecialchars($orderCurrencySymbol); ?><?php echo number_format($existingCredit, 2); ?>
                                <input type="hidden" id="edit-credit-reduce-val" value="<?php echo htmlspecialchars(number_format($existingCredit, 2, '.', '')); ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="edit-credit-reduce-val" value="0.00">
                    <?php endif; ?>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Custom Reduce / Discount (<?php echo htmlspecialchars($orderCurrencySymbol); ?>)</label>
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
                            <strong class="font-semibold text-gray-900"><?php echo htmlspecialchars($orderCurrencySymbol); ?><span id="edit-prices-calc-gross">0.00</span></strong>
                        </div>

                        <?php if ($existingCouponReduce > 0): ?>
                            <div class="flex items-center justify-between pt-1.5 text-green-700">
                                <span>Coupon Discount <?php echo $existingCouponCode !== '' ? '(' . htmlspecialchars($existingCouponCode) . ')' : ''; ?>:</span>
                                <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol); ?><span id="edit-prices-calc-coupon"><?php echo number_format($existingCouponReduce, 2); ?></span></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($existingGiftVoucherReduce > 0): ?>
                            <div class="flex items-center justify-between pt-1.5 text-purple-700">
                                <span>Gift Voucher <?php echo $existingGiftVoucherCode !== '' ? '(' . htmlspecialchars($existingGiftVoucherCode) . ')' : ''; ?>:</span>
                                <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol); ?><span id="edit-prices-calc-giftvoucher"><?php echo number_format($existingGiftVoucherReduce, 2); ?></span></strong>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between pt-1.5 text-orange-700">
                            <span>Custom Discount:</span>
                            <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol); ?><span id="edit-prices-calc-discount">0.00</span></strong>
                        </div>

                        <?php if ($existingCredit > 0): ?>
                            <div class="flex items-center justify-between pt-1.5 text-blue-700">
                                <span>Store Credit:</span>
                                <strong class="font-semibold">-<?php echo htmlspecialchars($orderCurrencySymbol); ?><span id="edit-prices-calc-credit"><?php echo number_format($existingCredit, 2); ?></span></strong>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between pt-2 text-base font-bold text-gray-900 border-t border-gray-200">
                            <span>Net Order Total:</span>
                            <strong class="text-base text-gray-900 font-bold"><?php echo htmlspecialchars($orderCurrencySymbol); ?><span id="edit-prices-calc-total">0.00</span></strong>
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

<script src="<?php echo base_url('assets/js/pos_message_modal.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/compliance_doc_modal.js'); ?>"></script>
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

document.getElementById('editOrderPricesForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-item-prices');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Updating...';
    }

    const formData = new FormData(this);

    fetch('index.php?page=posorders&action=update_item_prices', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeEditPricesModal();
            if (typeof window.showPosMessageModal === 'function') {
                window.showPosMessageModal({
                    title: 'Order Updated',
                    message: data.message || 'Order item prices updated successfully.',
                    tone: 'success',
                    onClose: function() { window.location.reload(); }
                });
            } else {
                window.location.reload();
            }
        } else {
            if (typeof window.showPosMessageModal === 'function') {
                window.showPosMessageModal({
                    title: 'Update Failed',
                    message: data.message || 'Failed to update order item prices',
                    tone: 'error'
                });
            } else {
                console.error(data.message || 'Failed to update order item prices');
            }
        }
    })
    .catch(err => {
        if (typeof window.showPosMessageModal === 'function') {
            window.showPosMessageModal({
                title: 'Error',
                message: 'An unexpected error occurred: ' + err.message,
                tone: 'error'
            });
        } else {
            console.error('An unexpected error occurred: ' + err.message);
        }
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
let pendingDispatchCancelId = null;

function cancelSingleDispatch(dispatchId) {
    pendingDispatchCancelId = dispatchId;
    const modal = document.getElementById('cancelDispatchModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeCancelDispatchModal() {
    pendingDispatchCancelId = null;
    const modal = document.getElementById('cancelDispatchModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

document.getElementById('confirmCancelDispatchBtn')?.addEventListener('click', function() {
    if (!pendingDispatchCancelId) return;
    const dispatchId = pendingDispatchCancelId;
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Cancelling...';

    fetch('index.php?page=dispatch&action=cancel_dispatch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dispatch_id: dispatchId })
    })
    .then(res => res.json())
    .then(data => {
        closeCancelDispatchModal();
        btn.disabled = false;
        btn.textContent = 'Confirm Cancel';
        if (data.success) {
            if (window.showPosMessageModal) {
                window.showPosMessageModal({
                    title: 'Dispatch Cancelled',
                    message: data.message || 'Dispatch cancelled successfully.',
                    tone: 'success',
                    onClose: function() { location.reload(); }
                });
            } else {
                location.reload();
            }
        } else {
            if (window.showPosMessageModal) {
                window.showPosMessageModal({
                    title: 'Cancellation Failed',
                    message: data.message || 'Failed to cancel dispatch.',
                    tone: 'error'
                });
            }
        }
    })
    .catch(err => {
        closeCancelDispatchModal();
        btn.disabled = false;
        btn.textContent = 'Confirm Cancel';
        if (window.showPosMessageModal) {
            window.showPosMessageModal({
                title: 'Error',
                message: 'An error occurred while cancelling the dispatch.',
                tone: 'error'
            });
        }
    });
});
</script>
