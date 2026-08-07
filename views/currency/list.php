<?php
$selectedCurrency = null;
$rateHistory = [];

// Helper to get currency flag, symbol, and accent styling
function getCurrencyMeta($code) {
    $code = strtoupper(trim($code));
    $map = [
        'USD' => ['flag' => '🇺🇸', 'symbol' => '$', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'EUR' => ['flag' => '🇪🇺', 'symbol' => '€', 'bg' => 'bg-blue-50 text-blue-700 border-blue-200'],
        'GBP' => ['flag' => '🇬🇧', 'symbol' => '£', 'bg' => 'bg-purple-50 text-purple-700 border-purple-200'],
        'JPY' => ['flag' => '🇯🇵', 'symbol' => '¥', 'bg' => 'bg-rose-50 text-rose-700 border-rose-200'],
        'CAD' => ['flag' => '🇨🇦', 'symbol' => 'CA$', 'bg' => 'bg-red-50 text-red-700 border-red-200'],
        'AUD' => ['flag' => '🇦🇺', 'symbol' => 'A$', 'bg' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'CHF' => ['flag' => '🇨🇭', 'symbol' => 'CHF', 'bg' => 'bg-red-50 text-red-700 border-red-200'],
        'AED' => ['flag' => '🇦🇪', 'symbol' => 'AED', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'SAR' => ['flag' => '🇸🇦', 'symbol' => 'SAR', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'SGD' => ['flag' => '🇸🇬', 'symbol' => 'S$', 'bg' => 'bg-rose-50 text-rose-700 border-rose-200'],
        'HKD' => ['flag' => '🇭🇰', 'symbol' => 'HK$', 'bg' => 'bg-red-50 text-red-700 border-red-200'],
        'DKK' => ['flag' => '🇩🇰', 'symbol' => 'kr', 'bg' => 'bg-red-50 text-red-700 border-red-200'],
        'NOK' => ['flag' => '🇳🇴', 'symbol' => 'kr', 'bg' => 'bg-blue-50 text-blue-700 border-blue-200'],
        'SEK' => ['flag' => '🇸🇪', 'symbol' => 'kr', 'bg' => 'bg-blue-50 text-blue-700 border-blue-200'],
        'INR' => ['flag' => '🇮🇳', 'symbol' => '₹', 'bg' => 'bg-orange-50 text-orange-700 border-orange-200'],
        'CNY' => ['flag' => '🇨🇳', 'symbol' => '¥', 'bg' => 'bg-red-50 text-red-700 border-red-200'],
    ];
    return $map[$code] ?? ['flag' => '🌐', 'symbol' => $code, 'bg' => 'bg-slate-50 text-slate-700 border-slate-200'];
}

$totalCurrencies = count($currencies ?? []);
$multiCountryCount = 0;

if (!empty($currencies)) {
    foreach ($currencies as $c) {
        $mappedRaw = !empty($c['mapped_countries']) ? $c['mapped_countries'] : CurrencyModel::getDefaultMappedCountries($c['currency_code']);
        $mappedList = array_filter(array_map('trim', explode(',', $mappedRaw)));
        if (count($mappedList) > 1) {
            $multiCountryCount++;
        }
    }
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Page Header & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl border border-indigo-100">
                    <i class="fa-solid fa-coins text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Currency Management</h1>
                    <p class="text-sm text-slate-500 font-normal">Manage foreign currencies, exchange rates, mapped regions, and CBIC/ICEGATE updates.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="openPdfModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <i class="fa-solid fa-file-pdf text-rose-500 text-base"></i>
                <span>PDF Import</span>
            </button>
            <a href="index.php?page=currency&action=addRecord" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <i class="fa-solid fa-plus text-sm"></i>
                <span>Add Currency</span>
            </a>
        </div>
    </div>

    <!-- Alert / Notice Messages -->
    <div id="noticeBox"></div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium shadow-sm transition-all">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span>Operation completed successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="flex items-center gap-3 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium shadow-sm transition-all">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-lg"></i>
            <span>An error occurred while performing the operation.</span>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-globe"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Currencies</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= $totalCurrencies ?></h3>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Base Currency</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-0.5">INR <span class="text-sm font-medium text-slate-500">(₹)</span></h3>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-network-wired"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Multi-Country Mapped</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= $multiCountryCount ?></h3>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rate Source</p>
                <h3 class="text-lg font-bold text-slate-900 mt-0.5">CBIC / ICEGATE</h3>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-sm"></i>
            </div>
            <input type="text" id="currencySearchInput" onkeyup="filterCurrenciesTable()" placeholder="Search code, name, unit or country code..." 
                   class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
            <button onclick="setQuickFilter('all')" id="filterBtnAll" class="quick-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs transition-all">
                All Currencies
            </button>
            <button onclick="setQuickFilter('major')" id="filterBtnMajor" class="quick-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-all">
                Major (USD/EUR/GBP/JPY)
            </button>
            <button onclick="setQuickFilter('multi')" id="filterBtnMulti" class="quick-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-all">
                Multi-Region Mapped
            </button>
            <span id="currencyCountDisplay" class="text-xs font-medium text-slate-500 ml-2 shrink-0">Showing <?= $totalCurrencies ?> of <?= $totalCurrencies ?></span>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (empty($currencies)): ?>
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-2xl">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">No Currencies Found</h3>
                    <p class="text-sm text-slate-500 mt-1">Get started by adding your first currency or importing exchange rates from PDF.</p>
                </div>
                <a href="index.php?page=currency&action=addRecord" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Add New Currency</span>
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="currencyTable">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Code & Flag</th>
                            <th class="py-3.5 px-5">Name & Unit</th>
                            <th class="py-3.5 px-5">Mapped Countries</th>
                            <th class="py-3.5 px-5 text-right">Import Rate (₹)</th>
                            <th class="py-3.5 px-5 text-right">Export Rate (₹)</th>
                            <th class="py-3.5 px-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                        <?php foreach ($currencies as $curr): 
                            $meta = getCurrencyMeta($curr['currency_code']);
                            $mappedRaw = !empty($curr['mapped_countries']) ? $curr['mapped_countries'] : CurrencyModel::getDefaultMappedCountries($curr['currency_code']);
                            $mappedList = array_filter(array_map('trim', explode(',', $mappedRaw)));
                            $displaySym = !empty($curr['display_symbol']) ? $curr['display_symbol'] : $meta['symbol'];
                        ?>
                            <tr class="currency-row hover:bg-slate-50/80 transition-colors" 
                                data-code="<?= strtolower(htmlspecialchars($curr['currency_code'])) ?>"
                                data-name="<?= strtolower(htmlspecialchars($curr['currency_name'])) ?>"
                                data-unit="<?= strtolower(htmlspecialchars($curr['currency_unit'])) ?>"
                                data-mapped="<?= strtolower(htmlspecialchars($mappedRaw)) ?>"
                                data-is-multi="<?= count($mappedList) > 1 ? '1' : '0' ?>"
                                data-is-major="<?= in_array(strtoupper($curr['currency_code']), ['USD','EUR','GBP','JPY','AUD','CAD']) ? '1' : '0' ?>">
                                
                                <!-- Code & Flag -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl shrink-0" title="<?= htmlspecialchars($curr['currency_code']) ?>"><?= $meta['flag'] ?></span>
                                        <div>
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg border text-xs font-bold tracking-wide <?= $meta['bg'] ?? 'bg-slate-50 text-slate-700 border-slate-200' ?>">
                                                <span><?= strtoupper(htmlspecialchars($curr['currency_code'])) ?></span>
                                                <span class="text-slate-400 font-normal">|</span>
                                                <span class="font-bold"><?= htmlspecialchars($displaySym) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Name & Unit -->
                                <td class="py-4 px-5">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($curr['currency_name']) ?></div>
                                    <div class="text-xs text-slate-500 font-normal mt-0.5">
                                        Unit: <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-mono font-medium"><?= htmlspecialchars($curr['currency_unit']) ?></span>
                                    </div>
                                </td>

                                <!-- Mapped Countries -->
                                <td class="py-4 px-5">
                                    <?php if (!empty($mappedList)): ?>
                                        <div class="flex flex-wrap items-center gap-1 max-w-xs" title="<?= htmlspecialchars(implode(', ', $mappedList)) ?>">
                                            <?php foreach (array_slice($mappedList, 0, 6) as $tag): ?>
                                                <span class="inline-block bg-slate-100 text-slate-800 border border-slate-200 px-2 py-0.5 rounded text-[11px] font-mono font-semibold">
                                                    <?= htmlspecialchars($tag) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($mappedList) > 6): ?>
                                                <span class="inline-block bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded text-[11px] font-semibold cursor-help" title="<?= htmlspecialchars(implode(', ', array_slice($mappedList, 6))) ?>">
                                                    +<?= count($mappedList) - 6 ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">None mapped</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Import Rate -->
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200/60 font-mono font-bold text-sm">
                                        <span class="text-xs text-emerald-500">₹</span>
                                        <span><?= number_format($curr['rate_import'], 6) ?></span>
                                    </div>
                                </td>

                                <!-- Export Rate -->
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1 text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200/60 font-mono font-bold text-sm">
                                        <span class="text-xs text-blue-500">₹</span>
                                        <span><?= number_format($curr['rate_export'], 6) ?></span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-5 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <a href="index.php?page=currency&action=addRecord&id=<?= $curr['id'] ?>" 
                                           class="p-2 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Edit Currency">
                                            <i class="fa-solid fa-pen-to-square text-base"></i>
                                        </a>
                                        <button onclick="openHistory(<?= htmlspecialchars(json_encode($curr)) ?>)" 
                                                class="p-2 text-slate-600 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Rate History">
                                            <i class="fa-solid fa-clock-rotate-left text-base"></i>
                                        </button>
                                        <button onclick="confirmDeactivate(event, '<?= $curr['id'] ?>', '<?= htmlspecialchars($curr['currency_code']) ?>')" 
                                                class="p-2 text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Deactivate Currency">
                                            <i class="fa-solid fa-trash-can text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PDF Import Modal -->
<div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-50 text-rose-600 rounded-xl border border-rose-100">
                    <i class="fa-solid fa-file-pdf text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">PDF Rate Import</h3>
                    <p class="text-xs text-slate-500">Upload official CBIC/ICEGATE exchange rate notification PDF.</p>
                </div>
            </div>
            <button onclick="closeModal('pdfModal')" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-all">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6">
            <form id="pdfUploadForm" onsubmit="handlePdfParse(event)" class="space-y-4">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Option A: Upload PDF File</label>
                    <div class="border-2 border-dashed border-slate-300 hover:border-indigo-500 bg-slate-50 hover:bg-indigo-50/30 rounded-2xl p-6 text-center cursor-pointer transition-all"
                         onclick="document.getElementById('pdfFileInput').click()">
                        <div class="w-12 h-12 mx-auto rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-3">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="text-sm font-semibold text-slate-800">Click to select Exchange Rate PDF</div>
                        <div class="text-xs text-slate-500 mt-1">Supports official exchange rate notification documents</div>
                        <input type="file" id="pdfFileInput" accept=".pdf" class="hidden" onchange="updateFileName(this)">
                        <div id="pdfFileName" class="mt-3 text-xs font-bold text-indigo-600"></div>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Option B: Local File Path</label>
                    <input type="text" id="pdfFilePathInput" placeholder="e.g. C:\Users\Admin\Downloads\exchange_rate.pdf" 
                           class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" id="parsePdfBtn" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        <span>Parse PDF & Preview Rates</span>
                    </button>
                </div>
            </form>

            <!-- Preview Section -->
            <div id="pdfPreviewSection" class="hidden pt-4 border-t border-slate-200 space-y-4">
                <div id="pdfMetaBadge" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex flex-wrap items-center justify-between gap-3 text-xs font-semibold text-slate-700"></div>
                
                <p class="text-xs text-slate-500">Review rates parsed from PDF. Uncheck any currency you do not wish to update.</p>
                
                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 font-bold text-slate-600 uppercase sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3 w-10 text-center">
                                    <input type="checkbox" id="selectAllPdf" checked onclick="toggleAllCheckboxes('pdfModal', this.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="py-2.5 px-3">Code</th>
                                <th class="py-2.5 px-3">Name</th>
                                <th class="py-2.5 px-3">Unit</th>
                                <th class="py-2.5 px-3">Import Rate</th>
                                <th class="py-2.5 px-3">Export Rate</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="pdfPreviewBody" class="divide-y divide-slate-100 font-medium"></tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button onclick="closeModal('pdfModal')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all">Cancel</button>
                    <button id="applyPdfRatesBtn" onclick="applyParsedRates('pdfModal', 'PDF')" class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Apply Selected Rates to Database</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full max-h-[85vh] flex flex-col overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-50 text-amber-600 rounded-xl border border-amber-100">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                </div>
                <div>
                    <h3 id="historyTitle" class="text-lg font-bold text-slate-900">Rate History</h3>
                    <p class="text-xs text-slate-500">Historical exchange rate logs for this currency.</p>
                </div>
            </div>
            <button onclick="closeModal('historyModal')" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-all">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 font-bold text-slate-600 uppercase">
                        <tr>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Import Rate (₹)</th>
                            <th class="py-3 px-4 text-right">Export Rate (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody" class="divide-y divide-slate-100 font-medium"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Notice / Confirmation Modal (Adheres to no-js-alert-use-modal.mdc) -->
<div id="noticeModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150 p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div id="noticeIcon" class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <h3 id="noticeTitle" class="text-lg font-bold text-slate-900">Notice</h3>
        </div>
        <div id="noticeMessage" class="text-sm text-slate-600 leading-relaxed"></div>
        <div class="flex items-center justify-end gap-3 pt-2">
            <button id="noticeCancelBtn" onclick="closeModal('noticeModal')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all hidden">Cancel</button>
            <button id="noticeConfirmBtn" onclick="closeModal('noticeModal')" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all">OK</button>
        </div>
    </div>
</div>

<script>
    let currentParsedData = {
        pdfModal: null
    };

    function showNotice(title, message, isError = false) {
        document.getElementById('noticeTitle').textContent = title;
        document.getElementById('noticeMessage').innerHTML = message;
        
        const iconContainer = document.getElementById('noticeIcon');
        if (isError) {
            iconContainer.className = 'w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0';
            iconContainer.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
        } else {
            iconContainer.className = 'w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0';
            iconContainer.innerHTML = '<i class="fa-solid fa-circle-info"></i>';
        }

        document.getElementById('noticeCancelBtn').classList.add('hidden');
        const confirmBtn = document.getElementById('noticeConfirmBtn');
        confirmBtn.textContent = 'OK';
        confirmBtn.onclick = function() { closeModal('noticeModal'); };

        document.getElementById('noticeModal').classList.remove('hidden');
    }

    function confirmDeactivate(event, id, code) {
        event.preventDefault();
        document.getElementById('noticeTitle').textContent = 'Confirm Deactivation';
        document.getElementById('noticeMessage').textContent = 'Are you sure you want to deactivate currency "' + code + '"?';
        
        const iconContainer = document.getElementById('noticeIcon');
        iconContainer.className = 'w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0';
        iconContainer.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';

        const cancelBtn = document.getElementById('noticeCancelBtn');
        cancelBtn.classList.remove('hidden');

        const btn = document.getElementById('noticeConfirmBtn');
        btn.textContent = 'Yes, Deactivate';
        btn.className = 'px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all';
        btn.onclick = function() {
            window.location.href = 'index.php?page=currency&action=deleteRecord&id=' + id;
        };
        
        document.getElementById('noticeModal').classList.remove('hidden');
    }

    function openPdfModal() {
        document.getElementById('pdfModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function updateFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('pdfFileName').textContent = 'Selected: ' + input.files[0].name;
            document.getElementById('pdfFilePathInput').value = '';
        }
    }

    function handlePdfParse(event) {
        event.preventDefault();
        const fileInput = document.getElementById('pdfFileInput');
        const pathInput = document.getElementById('pdfFilePathInput').value.trim();
        
        if (!fileInput.files.length && !pathInput) {
            showNotice('Validation Error', 'Please select a PDF file to upload or specify a local file path.', true);
            return;
        }

        const formData = new FormData();
        if (fileInput.files.length > 0) {
            formData.append('exchange_rate_pdf', fileInput.files[0]);
        } else {
            formData.append('file_path', pathInput);
        }

        const btn = document.getElementById('parsePdfBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> <span>Parsing PDF...</span>';

        fetch('index.php?page=currency&action=uploadPdfPreview', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-magnifying-glass text-xs"></i> <span>Parse PDF & Preview Rates</span>';

            if (!data.success) {
                showNotice('Parse Failed', data.message || 'Could not parse currency rates from PDF.', true);
                return;
            }

            currentParsedData.pdfModal = data;
            renderPreviewTable('pdfModal', data);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-magnifying-glass text-xs"></i> <span>Parse PDF & Preview Rates</span>';
            showNotice('Error', 'Server error while parsing PDF: ' + err.message, true);
        });
    }

    function renderPreviewTable(modalId, data) {
        const section = document.getElementById('pdfPreviewSection');
        const metaBadge = document.getElementById('pdfMetaBadge');
        const tbody = document.getElementById('pdfPreviewBody');

        metaBadge.innerHTML = `
            <div><span class="text-slate-400">Notification:</span> <strong class="text-slate-900">${data.notification_no || 'N/A'}</strong></div>
            <div><span class="text-slate-400">Effective Date:</span> <strong class="text-slate-900">${data.effective_date || 'Today'}</strong></div>
            <div><span class="text-slate-400">Parsed Currencies:</span> <strong class="text-indigo-600">${data.rates.length}</strong></div>
        `;

        tbody.innerHTML = '';
        data.rates.forEach((item, idx) => {
            const importChange = item.import_changed ? `<span class="bg-indigo-50 text-indigo-700 font-bold px-1.5 py-0.5 rounded">₹${item.rate_import}</span> <span class="text-slate-400 text-[10px]">(was ₹${item.current_import_rate})</span>` : `₹${item.rate_import}`;
            const exportChange = item.export_changed ? `<span class="bg-indigo-50 text-indigo-700 font-bold px-1.5 py-0.5 rounded">₹${item.rate_export}</span> <span class="text-slate-400 text-[10px]">(was ₹${item.current_export_rate})</span>` : `₹${item.rate_export}`;
            const statusBadge = !item.exists_in_db ? `<span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold">NEW</span>` : (item.import_changed || item.export_changed ? `<span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded text-[10px] font-bold">UPDATED</span>` : `<span class="bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold">UNCHANGED</span>`);

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="py-2.5 px-3 text-center"><input type="checkbox" class="rate-checkbox-${modalId} rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-index="${idx}" checked></td>
                    <td class="py-2.5 px-3 font-bold text-slate-900">${item.currency_code}</td>
                    <td class="py-2.5 px-3 text-slate-700">${item.currency_name}</td>
                    <td class="py-2.5 px-3 text-slate-500 font-mono">${item.currency_unit}</td>
                    <td class="py-2.5 px-3 font-mono">${importChange}</td>
                    <td class="py-2.5 px-3 font-mono">${exportChange}</td>
                    <td class="py-2.5 px-3 text-center">${statusBadge}</td>
                </tr>
            `;
        });

        section.classList.remove('hidden');
    }

    function toggleAllCheckboxes(modalId, isChecked) {
        document.querySelectorAll(`.rate-checkbox-${modalId}`).forEach(cb => cb.checked = isChecked);
    }

    function applyParsedRates(modalId, defaultSource) {
        const data = currentParsedData[modalId];
        if (!data || !data.rates) return;

        const selectedRates = [];
        document.querySelectorAll(`.rate-checkbox-${modalId}:checked`).forEach(cb => {
            const idx = parseInt(cb.getAttribute('data-index'));
            if (data.rates[idx]) {
                selectedRates.push(data.rates[idx]);
            }
        });

        if (selectedRates.length === 0) {
            showNotice('Selection Required', 'Please select at least one currency rate to apply.', true);
            return;
        }

        const payload = {
            rates: selectedRates,
            effective_date: data.effective_date || '',
            notification_no: data.notification_no || '',
            source: defaultSource
        };

        const btn = document.getElementById('applyPdfRatesBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> <span>Applying...</span>';

        fetch('index.php?page=currency&action=applyBulkRates', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check text-xs"></i> <span>Apply Selected Rates to Database</span>';

            if (res.success) {
                closeModal(modalId);
                window.location.href = 'index.php?page=currency&action=list&success=1';
            } else {
                showNotice('Update Failed', res.message || 'Failed to update database.', true);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check text-xs"></i> <span>Apply Selected Rates to Database</span>';
            showNotice('Error', 'Error applying rates: ' + err.message, true);
        });
    }

    function openHistory(currency) {
        document.getElementById('historyTitle').textContent = currency.currency_code + ' - Rate History';
        
        fetch('index.php?page=currency&action=getRateHistory&code=' + currency.currency_code)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('historyBody');
                tbody.innerHTML = '';
                
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="py-6 text-center text-slate-400">No history records found</td></tr>';
                } else {
                    data.forEach(record => {
                        const row = `<tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 px-4 font-mono text-slate-700">${record.rate_date}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-semibold text-emerald-700">₹${parseFloat(record.rate_import).toFixed(6)}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-semibold text-blue-700">₹${parseFloat(record.rate_export).toFixed(6)}</td>
                        </tr>`;
                        tbody.innerHTML += row;
                    });
                }
                
                document.getElementById('historyModal').classList.remove('hidden');
            })
            .catch(err => {
                showNotice('Error', 'Failed to fetch rate history: ' + err.message, true);
            });
    }

    // Filter functionality
    function filterCurrenciesTable() {
        const input = document.getElementById('currencySearchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.currency-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const code = row.getAttribute('data-code') || '';
            const name = row.getAttribute('data-name') || '';
            const unit = row.getAttribute('data-unit') || '';
            const mapped = row.getAttribute('data-mapped') || '';

            const isMatch = code.includes(input) || name.includes(input) || unit.includes(input) || mapped.includes(input);
            
            if (isMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('currencyCountDisplay').textContent = `Showing ${visibleCount} of ${rows.length}`;
    }

    function setQuickFilter(type) {
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.className = 'quick-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-all';
        });

        const activeBtn = type === 'all' ? document.getElementById('filterBtnAll') :
                         type === 'major' ? document.getElementById('filterBtnMajor') :
                         document.getElementById('filterBtnMulti');
        
        if (activeBtn) {
            activeBtn.className = 'quick-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs transition-all';
        }

        const rows = document.querySelectorAll('.currency-row');
        let visibleCount = 0;

        rows.forEach(row => {
            if (type === 'all') {
                row.style.display = '';
                visibleCount++;
            } else if (type === 'major') {
                if (row.getAttribute('data-is-major') === '1') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            } else if (type === 'multi') {
                if (row.getAttribute('data-is-multi') === '1') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        document.getElementById('currencyCountDisplay').textContent = `Showing ${visibleCount} of ${rows.length}`;
    }
</script>
