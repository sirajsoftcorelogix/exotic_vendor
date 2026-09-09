<?php
$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$searchValue = $h($search ?? '');
$skuValue = $h($sku ?? '');
$itemCodeValue = $h($item_code ?? '');
$purchasedValue = (string) ($purchased ?? 'no');
$dateFromValue = $h($date_from ?? '');
$dateToValue = $h($date_to ?? '');
$publisherValue = (string) ($publisher ?? '');
$publisherIdValue = (int) ($publisher_id ?? 0);
$vendorValue = (string) ($vendor ?? '');
$vendorIdValue = (int) ($vendor_id ?? 0);
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$limit = (int) ($limit ?? 20);
$totalRecords = (int) ($totalRecords ?? 0);
$tableReady = !empty($table_ready);
$canRun = !empty($can_run);
$rows = is_array($rows ?? null) ? $rows : [];
$exportQuery = (string) ($export_query ?? '');
$queryBase = [
    'page' => 'replenishment_buy_report',
    'action' => 'list',
    'search_text' => (string) ($search ?? ''),
    'sku' => (string) ($sku ?? ''),
    'item_code' => (string) ($item_code ?? ''),
    'purchased' => $purchasedValue,
    'date_from' => (string) ($date_from ?? ''),
    'date_to' => (string) ($date_to ?? ''),
    'publisher' => $publisherValue,
    'publisher_id' => $publisherIdValue,
    'vendor' => $vendorValue,
    'vendor_id' => $vendorIdValue,
    'limit' => $limit,
];
$inputClass = 'w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white';
$autocompleteInputClass = $inputClass;
$lookbackSourceLabels = [
    'product' => 'From product',
    'publisher' => 'From publisher',
    'vendor' => 'From vendor',
    'global' => 'Global',
];
$thInfo = static function (string $label, string $help, bool $right = false) use ($h): string {
    $rowClass = $right ? 'inline-flex items-center justify-end gap-1.5 w-full' : 'inline-flex items-center gap-1.5';
    return '<span class="' . $rowClass . '">'
        . '<span>' . $h($label) . '</span>'
        . '<i class="replenish-col-info fas fa-info-circle text-gray-400 hover:text-amber-700 cursor-help text-[12px] normal-case tracking-normal" data-help="' . $h($help) . '" aria-label="' . $h($help) . '"></i>'
        . '</span>';
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-clipboard-list text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Products · Daily replenishment</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Replenishment <span class="text-amber-800">buy report</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Books that sold yesterday and need purchase because available stock (physical + pending PO) is below the purchase threshold of period sales.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row shrink-0 gap-2">
                <?php if ($tableReady): ?>
                <a href="<?php echo $h('index.php?' . $exportQuery); ?>"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-800 text-sm font-semibold hover:bg-emerald-100">
                    <i class="fas fa-file-excel text-xs" aria-hidden="true"></i>
                    Download Excel
                </a>
                <?php endif; ?>
                <?php if ($canRun): ?>
                <button type="button" id="runReplenishmentJobBtn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] disabled:opacity-60 disabled:cursor-not-allowed disabled:pointer-events-none">
                    <i class="fas fa-play text-xs opacity-95" aria-hidden="true"></i>
                    Run for yesterday
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$tableReady): ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-semibold">Database setup required</p>
            <p class="mt-1">Run <code class="text-xs bg-white px-1 rounded">sql/create_replenishment_buy_report.sql</code>.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-visible">
            <form method="get" class="p-5 border-b border-gray-100 space-y-4 overflow-visible">
                <input type="hidden" name="page" value="replenishment_buy_report">
                <input type="hidden" name="action" value="list">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Quick search</label>
                        <input type="text" name="search_text" value="<?php echo $searchValue; ?>" placeholder="SKU, item code, or title"
                            class="<?php echo $inputClass; ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">SKU</label>
                        <input type="text" name="sku" value="<?php echo $skuValue; ?>" placeholder="SKU"
                            class="<?php echo $inputClass; ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Item code</label>
                        <input type="text" name="item_code" value="<?php echo $itemCodeValue; ?>" placeholder="Item code"
                            class="<?php echo $inputClass; ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Purchased</label>
                        <select name="purchased" class="<?php echo $inputClass; ?>">
                            <option value="no" <?php echo $purchasedValue === 'no' ? 'selected' : ''; ?>>No</option>
                            <option value="yes" <?php echo $purchasedValue === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="all" <?php echo $purchasedValue === 'all' ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Date from</label>
                        <input type="date" name="date_from" value="<?php echo $dateFromValue; ?>" class="<?php echo $inputClass; ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Date to</label>
                        <input type="date" name="date_to" value="<?php echo $dateToValue; ?>" class="<?php echo $inputClass; ?>">
                    </div>
                    <?php renderPartial('views/shared/partials/order_filter_autocomplete_field.php', [
                        'field_id' => 'replenish_publisher',
                        'field_name' => 'publisher',
                        'field_label' => 'Publisher',
                        'field_placeholder' => 'Type to search publisher...',
                        'field_value' => $publisherValue,
                        'search_url' => base_url('?page=orders&action=search_filter_publishers&q='),
                        'hidden_id' => 'replenish_publisher_id',
                        'hidden_name' => 'publisher_id',
                        'hidden_value' => $publisherIdValue > 0 ? (string) $publisherIdValue : '',
                        'input_class' => $autocompleteInputClass,
                        'auto_search' => true,
                    ]); ?>
                    <?php renderPartial('views/shared/partials/order_filter_autocomplete_field.php', [
                        'field_id' => 'replenish_vendor',
                        'field_name' => 'vendor',
                        'field_label' => 'Vendor',
                        'field_placeholder' => 'Type to search vendor...',
                        'field_value' => $vendorValue,
                        'search_url' => base_url('?page=orders&action=search_filter_vendors&q='),
                        'hidden_id' => 'replenish_vendor_id',
                        'hidden_name' => 'vendor_id',
                        'hidden_value' => $vendorIdValue > 0 ? (string) $vendorIdValue : '',
                        'input_class' => $autocompleteInputClass,
                        'auto_search' => true,
                    ]); ?>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                        <i class="fas fa-search text-xs" aria-hidden="true"></i>
                        Search
                    </button>
                    <a href="?page=replenishment_buy_report&amp;action=list" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">
                        Reset
                    </a>
                    <a href="<?php echo $h('index.php?' . $exportQuery); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 text-sm font-semibold hover:bg-emerald-100">
                        <i class="fas fa-file-excel text-xs" aria-hidden="true"></i>
                        Download Excel
                    </a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs text-gray-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold"><?php echo $thInfo('Sales date', 'The sales day this row was generated for (yesterday when you click Run for yesterday).'); ?></th>
                            <th class="px-4 py-3 font-semibold"><?php echo $thInfo('SKU / Item', 'Product SKU and item code. Click the SKU to open product details. Hover the item code to see the book title.'); ?></th>
                            <th class="px-4 py-3 font-semibold"><?php echo $thInfo('Publisher / Vendor', 'Publisher for this SKU, with the primary vendor underneath.'); ?></th>
                            <th class="px-4 py-3 font-semibold"><?php echo $thInfo('Lookback', 'How many months of sales history we use. The small label under the number shows where this came from: product, publisher, vendor, or global setting. This is a time window, not units sold.'); ?></th>
                            <th class="px-4 py-3 font-semibold text-right"><?php echo $thInfo("Y'day sold", 'Units of this SKU sold on the sales date only (not the full lookback period).', true); ?></th>
                            <th class="px-4 py-3 font-semibold text-right"><?php echo $thInfo('Sold', 'Total units sold during the lookback months. Buy qty and purchase threshold are calculated from this number, not from yesterday sold.', true); ?></th>
                            <th class="px-4 py-3 font-semibold text-right"><?php echo $thInfo('Available Stock', 'Physical stock + pending PO. Compared with the purchase threshold. Hover the i icon on each row for that SKU’s calculation.', true); ?></th>
                            <th class="px-4 py-3 font-semibold text-right"><?php echo $thInfo('Purchase threshold', 'Minimum stock needed to avoid a buy: period sold × threshold %. The small % is the global setting. A buy is recommended when available stock is below this quantity.', true); ?></th>
                            <th class="px-4 py-3 font-semibold text-right"><?php echo $thInfo('Buy', 'How many to buy: period sold × minimum stock %. The small % is the global setting. This is saved on the product when the threshold rule is met.', true); ?></th>
                            <th class="px-4 py-3 font-semibold"><?php echo $thInfo('Purchased', 'Mark Yes after you have purchased this item. Click to toggle.', true); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($rows === []): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">No replenishment items for these filters.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $isPurchased = (int) ($row['purchased'] ?? 0) === 1;
                            $lookbackKey = strtolower(trim((string) ($row['lookback_source'] ?? '')));
                            $lookbackLabel = $lookbackSourceLabels[$lookbackKey] ?? ($lookbackKey !== '' ? ('From ' . $lookbackKey) : '—');
                            $lookbackMonths = (int) ($row['lookback_months'] ?? 0);
                            $physicalStock = (int) ($row['physical_stock'] ?? 0);
                            $pendingPoQty = (int) ($row['pending_po_qty'] ?? 0);
                            $availableStock = (int) ($row['available_stock'] ?? 0);
                            $availableHelp = 'Available stock = Physical stock ' . $physicalStock . ' + Pending PO ' . $pendingPoQty;
                            $publisherName = trim((string) ($row['publisher_name'] ?? ''));
                            $vendorName = trim((string) ($row['vendor_name'] ?? ''));
                            $bookTitle = trim((string) ($row['title'] ?? ''));
                            ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600"><?php echo $h($row['run_date_display'] ?? $row['run_date'] ?? ''); ?></td>
                                <td class="px-4 py-3">
                                    <a class="text-amber-800 font-medium hover:underline" href="<?php echo $h(base_url('?page=products&action=detail&id=' . (int) ($row['product_id'] ?? 0))); ?>">
                                        <?php echo $h($row['sku'] ?? ''); ?>
                                    </a>
                                    <div class="text-xs text-gray-400 cursor-help" title="<?php echo $h($bookTitle); ?>"><?php echo $h($row['item_code'] ?? ''); ?></div>
                                </td>
                                <td class="px-4 py-3 max-w-[14rem]">
                                    <div class="text-gray-800 truncate" title="<?php echo $h($publisherName); ?>"><?php echo $h($publisherName !== '' ? $publisherName : '—'); ?></div>
                                    <div class="text-xs text-gray-400 truncate" title="<?php echo $h($vendorName); ?>"><?php echo $h($vendorName !== '' ? $vendorName : '—'); ?></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-gray-800"><?php echo $lookbackMonths; ?> <?php echo $lookbackMonths === 1 ? 'month' : 'months'; ?></div>
                                    <div class="text-xs text-gray-400"><?php echo $h($lookbackLabel); ?></div>
                                </td>
                                <td class="px-4 py-3 text-right"><?php echo (int) ($row['yesterday_sold_qty'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right"><?php echo (int) ($row['numsold_replenishment'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center justify-end gap-1.5 w-full">
                                        <span class="font-semibold"><?php echo $availableStock; ?></span>
                                        <i class="replenish-col-info fas fa-info-circle text-gray-400 hover:text-amber-700 cursor-help text-[12px]" data-help="<?php echo $h($availableHelp); ?>" aria-label="<?php echo $h($availableHelp); ?>"></i>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div><?php echo (int) ($row['purchase_threshold_qty'] ?? 0); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo (int) ($row['purchase_threshold_percent'] ?? 0); ?>%</div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="font-semibold text-lime-800"><?php echo (int) ($row['replenishment_buy_qty'] ?? 0); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo (int) ($row['min_stock_percent'] ?? 0); ?>%</div>
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button"
                                        class="replenish-purchased-toggle px-3 py-1.5 rounded-lg text-xs font-semibold border <?php echo $isPurchased ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-900 border-amber-200'; ?>"
                                        data-id="<?php echo (int) ($row['id'] ?? 0); ?>"
                                        data-purchased="<?php echo $isPurchased ? '1' : '0'; ?>">
                                        <?php echo $isPurchased ? 'Yes' : 'No'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                <span><?php echo (int) $totalRecords; ?> items</span>
                <div class="flex items-center gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a class="px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50" href="<?php echo $h('?' . http_build_query(array_merge($queryBase, ['page_no' => $currentPage - 1]))); ?>">Prev</a>
                    <?php endif; ?>
                    <span>Page <?php echo $currentPage; ?> / <?php echo $totalPages; ?></span>
                    <?php if ($currentPage < $totalPages): ?>
                        <a class="px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50" href="<?php echo $h('?' . http_build_query(array_merge($queryBase, ['page_no' => $currentPage + 1]))); ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
<?php renderPartial('views/shared/partials/order_filter_autocomplete_script.php'); ?>
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const notice = function (message, tone) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, tone || 'info');
            return;
        }
        if (typeof window.showPosMessageModal === 'function') {
            window.showPosMessageModal({ title: 'Replenishment', message: message, tone: tone || 'info' });
        }
    };

    document.querySelectorAll('.replenish-purchased-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = parseInt(btn.getAttribute('data-id') || '0', 10);
            const currentlyYes = btn.getAttribute('data-purchased') === '1';
            fetch('index.php?page=replenishment_buy_report&action=mark_purchased', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, purchased: currentlyYes ? 0 : 1 })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    notice(data.message || 'Could not update.', 'error');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                notice('Could not update purchased status.', 'error');
            });
        });
    });

    const runBtn = document.getElementById('runReplenishmentJobBtn');
    if (runBtn) {
        runBtn.disabled = false;
        runBtn.removeAttribute('disabled');
        runBtn.addEventListener('click', function () {
            runBtn.disabled = true;
            fetch('index.php?page=replenishment_buy_report&action=run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (res) { return res.text(); })
            .then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error(text ? text.replace(/<[^>]+>/g, ' ').slice(0, 180) : 'Invalid server response');
                }
                notice(data.message || 'Job finished.', data.success ? 'success' : 'error');
                if (!data.success) {
                    runBtn.disabled = false;
                    return;
                }
                var params = new URLSearchParams(window.location.search);
                params.set('page', 'replenishment_buy_report');
                params.set('action', 'list');
                params.set('purchased', 'no');
                if (data.summary && data.summary.run_date) {
                    params.set('date_from', data.summary.run_date);
                    params.set('date_to', data.summary.run_date);
                    params.delete('run_date');
                }
                var nextSearch = '?' + params.toString();
                if (window.location.search === nextSearch) {
                    window.location.reload();
                    return;
                }
                window.location.search = nextSearch;
                window.setTimeout(function () {
                    runBtn.disabled = false;
                }, 1500);
            })
            .catch(function (err) {
                runBtn.disabled = false;
                notice((err && err.message) ? err.message : 'Could not run replenishment job.', 'error');
            });
        });
    }
});
</script>
<div id="replenishColTip" class="hidden fixed z-[200] w-64 max-w-[16rem] text-left text-[11px] leading-snug text-white bg-gray-900 rounded-lg px-3 py-2 shadow-lg pointer-events-none"></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tip = document.getElementById('replenishColTip');
    if (!tip) {
        return;
    }
    var placeTip = function (icon) {
        var help = icon.getAttribute('data-help') || '';
        if (help === '') {
            return;
        }
        tip.textContent = help;
        tip.classList.remove('hidden');
        var rect = icon.getBoundingClientRect();
        var tipWidth = tip.offsetWidth || 256;
        var tipHeight = tip.offsetHeight || 80;
        var left = rect.left;
        if (left + tipWidth > window.innerWidth - 8) {
            left = window.innerWidth - tipWidth - 8;
        }
        if (left < 8) {
            left = 8;
        }
        var top = rect.bottom + 8;
        if (top + tipHeight > window.innerHeight - 8) {
            top = rect.top - tipHeight - 8;
        }
        if (top < 8) {
            top = 8;
        }
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
    };
    document.querySelectorAll('.replenish-col-info').forEach(function (icon) {
        icon.addEventListener('mouseenter', function () { placeTip(icon); });
        icon.addEventListener('focus', function () { placeTip(icon); });
        icon.addEventListener('mouseleave', function () { tip.classList.add('hidden'); });
        icon.addEventListener('blur', function () { tip.classList.add('hidden'); });
        icon.setAttribute('tabindex', '0');
    });
    window.addEventListener('scroll', function () { tip.classList.add('hidden'); }, true);
});
</script>
