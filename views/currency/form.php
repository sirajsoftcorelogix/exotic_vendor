<?php
$codeVal = $currency ? strtoupper($currency['currency_code']) : '';
$defaultMapped = CurrencyModel::getDefaultMappedCountries($codeVal);
$mappedVal = ($currency && array_key_exists('mapped_countries', $currency) && $currency['mapped_countries'] !== null) 
             ? $currency['mapped_countries'] 
             : $defaultMapped;
$displaySymbolVal = $currency ? ($currency['display_symbol'] ?? '') : '';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Page Header & Back Button -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="index.php?page=currency&action=list" class="p-2.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 rounded-xl transition-all shadow-2xs" title="Back to List">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight"><?= $isEdit ? 'Edit Currency' : 'Add New Currency' ?></h1>
                <p class="text-xs text-slate-500 font-normal">Configure currency code, conversion rates, and region mappings.</p>
            </div>
        </div>

        <a href="index.php?page=currency&action=list" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl transition-all shadow-2xs">
            <i class="fa-solid fa-list text-xs"></i>
            <span>Back to Currencies</span>
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($successMessage)): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium shadow-2xs">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span><?= htmlspecialchars($successMessage) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium shadow-2xs space-y-2">
            <div class="flex items-center gap-2 font-bold text-rose-900">
                <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                <span>Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pl-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 sm:p-8">
        <form method="POST" class="space-y-6">
            
            <!-- Row 1: Code & Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="currency_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Currency Code <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="currency_code" name="currency_code" maxlength="3" 
                           value="<?= $currency ? strtoupper(htmlspecialchars($currency['currency_code'])) : '' ?>" 
                           <?= $isEdit ? 'readonly' : '' ?> required
                           placeholder="e.g. USD, EUR, GBP"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold uppercase text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all <?= $isEdit ? 'cursor-not-allowed opacity-75' : '' ?>">
                    <p class="text-[11px] text-slate-400 mt-1">3-letter ISO 4217 currency code (e.g. USD, EUR, JPY).</p>
                </div>

                <div>
                    <label for="currency_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Currency Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="currency_name" name="currency_name" 
                           value="<?= $currency ? htmlspecialchars($currency['currency_name']) : '' ?>" required
                           placeholder="e.g. US Dollar, Euro, British Pound"
                           class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
            </div>

            <!-- Row 2: Unit & Display Symbol -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="currency_unit" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Currency Unit <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="currency_unit" name="currency_unit" 
                           value="<?= $currency ? htmlspecialchars($currency['currency_unit']) : '' ?>" required
                           placeholder="e.g. 1 USD, 1 EUR, 100 JPY"
                           class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <p class="text-[11px] text-slate-400 mt-1">Denomination unit (e.g. 1 USD or 100 JPY).</p>
                </div>

                <div>
                    <label for="display_symbol" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Display Symbol
                    </label>
                    <input type="text" id="display_symbol" name="display_symbol" 
                           value="<?= htmlspecialchars($displaySymbolVal) ?>" 
                           placeholder="e.g. $, €, £, ¥, ₹, CA$, kr"
                           class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <p class="text-[11px] text-slate-400 mt-1">Symbol used on receipts and invoice views.</p>
                </div>
            </div>

            <!-- Row 3: Mapped Countries -->
            <div class="space-y-2">
                <label for="mapped_countries" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    Mapped Countries / ISO Country Codes
                </label>
                <input type="text" id="mapped_countries" name="mapped_countries" 
                       value="<?= htmlspecialchars($mappedVal) ?>" 
                       placeholder="e.g. DE, FR, IT, ES, NL, BE, AT, FI or GB, UK or US"
                       class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <p class="text-[11px] text-slate-400">
                    Comma-separated list of country codes or country names mapped to this currency (e.g., Eurozone countries for EUR, UK for GBP, Denmark for DKK, Norway for NOK).
                </p>

                <!-- Quick Presets -->
                <div class="pt-2 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 mr-1">Quick Presets:</span>
                    <button type="button" onclick="setPreset('DE, FR, IT, ES, NL, BE, AT, FI, GR, IE, PT, SK, SI, EE, LV, LT, CY, MT, LU, HR')" 
                            class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-200 transition-all">
                        Eurozone (EUR)
                    </button>
                    <button type="button" onclick="setPreset('GB, UK')" 
                            class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-200 transition-all">
                        UK (GBP)
                    </button>
                    <button type="button" onclick="setPreset('US')" 
                            class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-200 transition-all">
                        USA (USD)
                    </button>
                    <button type="button" onclick="setPreset('DK')" 
                            class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-200 transition-all">
                        Denmark (DKK)
                    </button>
                    <button type="button" onclick="setPreset('NO')" 
                            class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-200 transition-all">
                        Norway (NOK)
                    </button>
                </div>
            </div>

            <!-- Row 4: Import & Export Rates -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                <div>
                    <label for="rate_import" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Import Rate (₹) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">₹</div>
                        <input type="number" id="rate_import" name="rate_import" step="0.000001" min="0"
                               value="<?= $currency ? $currency['rate_import'] : '' ?>" required
                               placeholder="0.000000"
                               class="w-full pl-8 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-mono font-bold text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <div>
                    <label for="rate_export" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Export Rate (₹) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">₹</div>
                        <input type="number" id="rate_export" name="rate_export" step="0.000001" min="0"
                               value="<?= $currency ? $currency['rate_export'] : '' ?>" required
                               placeholder="0.000000"
                               class="w-full pl-8 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-mono font-bold text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                <a href="index.php?page=currency&action=list" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-all">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span><?= $isEdit ? 'Update Currency' : 'Save Currency' ?></span>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    function setPreset(val) {
        var el = document.getElementById('mapped_countries');
        if (el) {
            el.value = val;
        }
    }
</script>
