(function (window) {
    'use strict';

    var config = {
        modeOptions: [],
        getTargetTotal: function () { return 0; },
        getDisplayOrderTotal: null,
        onSubmit: null,
        submitButtonId: 'posPaymentModalSubmitBtn',
        highValueLimit: 200000,
        showCustomInvoiceField: false,
    };

    function posPaymentDateLocalYmd() {
        var d = new Date();
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function getPaymentCurrencyInfo() {
        var code = 'INR';
        var symbol = '₹';

        if (window.POS_CURRENCY_MODE === 'INR') {
            return {
                code: 'INR',
                symbol: '₹'
            };
        }

        var cartData = window.__posCartLastRetrieveData;
        if (cartData && typeof cartData === 'object') {
            if (cartData.currency_code) code = String(cartData.currency_code).trim().toUpperCase();
            if (cartData.currency_symbol) symbol = String(cartData.currency_symbol).trim();
        }

        if (code === 'INR' && window.POS_CURRENT_CUSTOMER_CURRENCY_CODE) {
            code = String(window.POS_CURRENT_CUSTOMER_CURRENCY_CODE).trim().toUpperCase();
            if (window.POS_CURRENT_CUSTOMER_CURRENCY_SYMBOL) symbol = String(window.POS_CURRENT_CUSTOMER_CURRENCY_SYMBOL).trim();
        } else if (code === 'INR' && window.POS_INITIAL_CUSTOMER && window.POS_INITIAL_CUSTOMER.currency_code) {
            var ic = window.POS_INITIAL_CUSTOMER;
            if (ic.currency_code) code = String(ic.currency_code).trim().toUpperCase();
            if (ic.currency_symbol) symbol = String(ic.currency_symbol).trim();
        }

        if (!symbol || (symbol === '₹' && code !== 'INR')) {
            if (code === 'INR') symbol = '₹';
            else if (code === 'USD') symbol = '$';
            else if (code === 'EUR') symbol = '€';
            else if (code === 'GBP') symbol = '£';
            else symbol = code;
        }

        return {
            code: code,
            symbol: symbol
        };
    }

    function getPaymentCurrencyCode() {
        return getPaymentCurrencyInfo().code;
    }

    function getPaymentCurrencySymbol() {
        return getPaymentCurrencyInfo().symbol;
    }

    function formatPaymentInr(amount) {
        var n = parseFloat(String(amount));
        if (!isFinite(n)) {
            n = 0;
        }
        var info = getPaymentCurrencyInfo();
        try {
            return new Intl.NumberFormat(info.code === 'INR' ? 'en-IN' : 'en-US', {
                style: 'currency',
                currency: info.code,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(n);
        } catch (e) {
            return info.symbol + ' ' + n.toFixed(2);
        }
    }

    function getPaymentSplitRowsContainer() {
        return document.getElementById('payment_split_rows');
    }

    function populatePaymentSplitModeSelect(selectEl, selectedMode) {
        if (!selectEl) {
            return;
        }
        var prev = String(selectedMode || selectEl.value || 'cash').toLowerCase();
        selectEl.innerHTML = '';
        var options = Array.isArray(config.modeOptions) ? config.modeOptions : [];
        var isWaivedAllowed = typeof window.isPosFollowUpWaivedCheckout === 'function' && window.isPosFollowUpWaivedCheckout();
        options.forEach(function (pair) {
            if (!Array.isArray(pair) || !pair[0]) {
                return;
            }
            var mode = String(pair[0]).toLowerCase();
            if (mode === 'waived' && !isWaivedAllowed) {
                return;
            }
            var opt = document.createElement('option');
            opt.value = String(pair[0]);
            opt.textContent = String(pair[1] || pair[0]);
            selectEl.appendChild(opt);
        });
        if (prev && selectEl.querySelector('option[value="' + prev.replace(/"/g, '') + '"]')) {
            selectEl.value = prev;
        } else if (selectEl.options.length) {
            selectEl.selectedIndex = 0;
        }
    }

    function syncPaymentSplitTxnHint(row) {
        if (!row) {
            return;
        }
        var mode = String(row.querySelector('.payment-split-mode')?.value || '').toLowerCase();
        var hint = row.querySelector('.payment-split-txn-hint');
        var txn = row.querySelector('.payment-split-txn');
        var need = mode === 'razorpay' || mode === 'cheque';
        if (hint) {
            hint.classList.toggle('hidden', !need);
        }
        if (txn) {
            txn.placeholder = need ? 'Required' : 'Optional';
        }
    }

    function bindPaymentSplitRow(row) {
        if (!row || row.dataset.bound === '1') {
            return;
        }
        row.dataset.bound = '1';
        row.querySelectorAll('input, select').forEach(function (el) {
            el.addEventListener('input', recalcPaymentSplitUi);
            el.addEventListener('change', recalcPaymentSplitUi);
        });
        var modeEl = row.querySelector('.payment-split-mode');
        if (modeEl) {
            modeEl.addEventListener('change', function () {
                syncPaymentSplitTxnHint(row);
            });
        }
        var removeBtn = row.querySelector('.payment-split-remove');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                var container = getPaymentSplitRowsContainer();
                if (!container || container.children.length <= 1) {
                    return;
                }
                row.remove();
                recalcPaymentSplitUi();
            });
        }
        syncPaymentSplitTxnHint(row);
    }

    function addPaymentSplitRow(mode, amount, txn) {
        var tpl = document.getElementById('payment_split_row_template');
        var container = getPaymentSplitRowsContainer();
        if (!tpl || !container) {
            return;
        }
        var node = tpl.content.cloneNode(true);
        container.appendChild(node);
        var row = container.lastElementChild;
        if (!row) {
            return;
        }
        var modeEl = row.querySelector('.payment-split-mode');
        var amtEl = row.querySelector('.payment-split-amount');
        var txnEl = row.querySelector('.payment-split-txn');
        populatePaymentSplitModeSelect(modeEl, mode || 'cash');
        if (modeEl && mode) {
            modeEl.value = mode;
        }
        if (amtEl != null && amount != null && amount !== '') {
            amtEl.value = String(amount);
        }
        if (txnEl && txn) {
            txnEl.value = txn;
        }
        bindPaymentSplitRow(row);
        recalcPaymentSplitUi();
    }

    function resetPaymentSplitRows(grandTotal) {
        var container = getPaymentSplitRowsContainer();
        if (!container) {
            return;
        }
        container.innerHTML = '';
        var isWaived = typeof window.isPosFollowUpWaivedCheckout === 'function' && window.isPosFollowUpWaivedCheckout();
        if (isWaived) {
            addPaymentSplitRow('waived', 0, '');
        } else {
            var total = parseFloat(String(grandTotal));
            addPaymentSplitRow('cash', isFinite(total) && total > 0 ? total : '', '');
        }
    }

    function collectAllPaymentSplitRowsFromUi() {
        var container = getPaymentSplitRowsContainer();
        if (!container) {
            return [];
        }
        var out = [];
        container.querySelectorAll('.payment-split-row').forEach(function (row) {
            var mode = String(row.querySelector('.payment-split-mode')?.value || '').trim().toLowerCase();
            var amount = parseFloat(String(row.querySelector('.payment-split-amount')?.value || ''));
            var txn = String(row.querySelector('.payment-split-txn')?.value || '').trim();
            if (!mode) {
                return;
            }
            if (mode === 'waived') {
                out.push({
                    mode: 'waived',
                    amount: 0,
                    transaction_id: txn,
                });
                return;
            }
            if (!isFinite(amount) || amount <= 0) {
                return;
            }
            out.push({
                mode: mode,
                amount: Math.round(amount * 100) / 100,
                transaction_id: txn,
            });
        });
        return out;
    }

    function isPendingPaymentMode(mode) {
        var m = String(mode || '').toLowerCase();
        return m === 'cod' || m === 'pay_on_pickup';
    }

    function getPaymentSplitAdvanceTotalFromUi() {
        var total = 0;
        collectAllPaymentSplitRowsFromUi().forEach(function (s) {
            if (!isPendingPaymentMode(s.mode)) {
                total += s.amount;
            }
        });
        return Math.round(total * 100) / 100;
    }

    function getPaymentSplitCodTotalFromUi() {
        var total = 0;
        collectAllPaymentSplitRowsFromUi().forEach(function (s) {
            if (isPendingPaymentMode(s.mode)) {
                total += s.amount;
            }
        });
        return Math.round(total * 100) / 100;
    }

    function paymentSplitHasCodFromUi() {
        return getPaymentSplitCodTotalFromUi() > 0.001;
    }

    function getPaymentSplitTotalFromUi() {
        var total = 0;
        collectAllPaymentSplitRowsFromUi().forEach(function (s) {
            total += s.amount;
        });
        return Math.round(total * 100) / 100;
    }

    function getTargetTotal() {
        var total = parseFloat(String(typeof config.getTargetTotal === 'function' ? config.getTargetTotal() : 0));
        return isFinite(total) ? total : 0;
    }

    function getDisplayOrderTotal() {
        if (typeof config.getDisplayOrderTotal === 'function') {
            var display = parseFloat(String(config.getDisplayOrderTotal()));
            if (isFinite(display)) {
                return display;
            }
        }
        return getTargetTotal();
    }

    function syncLegacyPaymentHiddenFields(splits, total) {
        var amountEl = document.getElementById('payment_amount');
        var modeEl = document.getElementById('payment_mode');
        var txnEl = document.getElementById('transaction_id');
        if (amountEl) {
            amountEl.value = String(total);
        }
        var primary = splits[0] || { mode: 'cash', transaction_id: '' };
        splits.forEach(function (s) {
            if (s.amount > primary.amount) {
                primary = s;
            }
        });
        if (modeEl) {
            modeEl.value = primary.mode || 'cash';
        }
        if (txnEl) {
            txnEl.value = primary.transaction_id || '';
        }
    }

    function syncCustomInvoiceNumberField() {
        if (!config.showCustomInvoiceField) {
            return;
        }
        var wrap = document.getElementById('customInvoiceNumberWrap');
        var input = document.getElementById('custom_invoice_number');
        if (!wrap) {
            return;
        }
        var stage = String(document.getElementById('payment_stage')?.value || 'final').toLowerCase();
        var amount = getPaymentSplitTotalFromUi();
        var total = getTargetTotal();
        var show = stage === 'final' && total > 0 && Math.abs(amount - total) <= 0.02;
        wrap.classList.toggle('hidden', !show);
        if (!show && input) {
            input.value = '';
        }
    }

    function syncPaymentSplitCurrencyHeaders() {
        var info = getPaymentCurrencyInfo();
        var labelText = 'Amount (' + info.symbol + ')';
        document.querySelectorAll('.payment-split-amount-header').forEach(function (el) {
            el.textContent = labelText;
        });
        document.querySelectorAll('.payment-split-amount-label').forEach(function (el) {
            el.textContent = labelText;
        });
    }

    function recalcPaymentSplitUi() {
        syncPaymentSplitCurrencyHeaders();
        var orderTotal = getTargetTotal();
        var displayTotal = getDisplayOrderTotal();
        var stage = String(document.getElementById('payment_stage')?.value || 'final').toLowerCase();

        var isZeroAdvance = stage === 'zero_advance';
        var splitSection = document.getElementById('payment_split_section');
        var zeroNotice = document.getElementById('zero_advance_notice');

        if (splitSection) {
            splitSection.classList.toggle('hidden', isZeroAdvance);
        }
        if (zeroNotice) {
            zeroNotice.classList.toggle('hidden', !isZeroAdvance);
        }

        var orderEl = document.getElementById('payment_summary_order');
        var paidEl = document.getElementById('payment_summary_paid');
        var balEl = document.getElementById('payment_summary_balance');
        var balLabelEl = document.getElementById('payment_summary_balance_label');
        var hintEl = document.getElementById('payment_summary_hint');
        var countEl = document.getElementById('payment_split_count');
        var totalEl = document.getElementById('payment_split_total');

        if (isZeroAdvance) {
            syncLegacyPaymentHiddenFields([{ mode: 'pay_on_pickup', amount: orderTotal, transaction_id: '' }], 0);
            if (orderEl) orderEl.textContent = formatPaymentInr(displayTotal);
            if (paidEl) paidEl.textContent = formatPaymentInr(0);
            if (balLabelEl) balLabelEl.textContent = 'Pending on pickup';
            if (balEl) {
                balEl.textContent = formatPaymentInr(orderTotal);
                balEl.className = 'mt-0.5 text-lg font-bold text-amber-700 tabular-nums';
            }
            if (countEl) countEl.textContent = '0';
            if (totalEl) totalEl.textContent = formatPaymentInr(0);
            if (hintEl) {
                hintEl.textContent = 'Zero advance payment — full amount ' + formatPaymentInr(orderTotal) + ' will be collected on store pickup.';
                hintEl.classList.remove('hidden');
            }
            syncCustomInvoiceNumberField();
            return;
        }

        var splits = collectAllPaymentSplitRowsFromUi();
        var splitTotal = getPaymentSplitTotalFromUi();
        var advanceTotal = getPaymentSplitAdvanceTotalFromUi();
        var codTotal = getPaymentSplitCodTotalFromUi();
        var hasCod = paymentSplitHasCodFromUi();
        var balance = Math.round((orderTotal - splitTotal) * 100) / 100;

        syncLegacyPaymentHiddenFields(splits, hasCod ? advanceTotal : splitTotal);

        var isPayOnPickup = splits.some(function (s) { return s.mode === 'pay_on_pickup'; });

        if (orderEl) {
            orderEl.textContent = formatPaymentInr(displayTotal);
        }
        if (paidEl) {
            paidEl.textContent = formatPaymentInr(hasCod ? advanceTotal : splitTotal);
        }
        if (balLabelEl) {
            balLabelEl.textContent = hasCod ? (isPayOnPickup ? 'Pending on pickup' : 'COD pending') : 'Balance';
        }
        if (balEl) {
            balEl.textContent = formatPaymentInr(hasCod ? codTotal : balance);
            if (hasCod) {
                balEl.className = 'mt-0.5 text-lg font-bold text-amber-700 tabular-nums';
            } else if (stage === 'final' && Math.abs(balance) < 0.02) {
                balEl.className = 'mt-0.5 text-lg font-bold text-emerald-700 tabular-nums';
            } else if (balance > 0.01) {
                balEl.className = 'mt-0.5 text-lg font-bold text-amber-700 tabular-nums';
            } else if (balance < -0.01) {
                balEl.className = 'mt-0.5 text-lg font-bold text-red-700 tabular-nums';
            } else {
                balEl.className = 'mt-0.5 text-lg font-bold text-emerald-700 tabular-nums';
            }
        }
        if (countEl) {
            countEl.textContent = String(splits.length);
        }
        if (totalEl) {
            totalEl.textContent = formatPaymentInr(splitTotal);
        }
        if (hintEl) {
            if (hasCod) {
                if (Math.abs(splitTotal - orderTotal) > 0.02) {
                    hintEl.textContent = 'Advance plus COD must equal order total (' + formatPaymentInr(orderTotal) + ').';
                    hintEl.classList.remove('hidden');
                } else if (codTotal > 0.001) {
                    hintEl.textContent = formatPaymentInr(codTotal) + (isPayOnPickup ? ' will be collected on store pickup.' : ' will be collected on delivery.');
                    hintEl.classList.remove('hidden');
                } else {
                    hintEl.classList.add('hidden');
                }
            } else if (stage === 'final' && balance > 0.02) {
                hintEl.textContent = 'Add ' + formatPaymentInr(balance) + ' more to match order total.';
                hintEl.classList.remove('hidden');
            } else if (stage === 'final' && balance < -0.02) {
                hintEl.textContent = 'Split total exceeds order total by ' + formatPaymentInr(Math.abs(balance)) + '.';
                hintEl.classList.remove('hidden');
            } else if ((stage === 'partial' || stage === 'advance') && splitTotal + 0.02 >= orderTotal && orderTotal > 0) {
                hintEl.textContent = 'Partial / advance must be less than order total.';
                hintEl.classList.remove('hidden');
            } else {
                hintEl.classList.add('hidden');
            }
        }
        syncCustomInvoiceNumberField();
    }

    function showSplitValidationError(message) {
        var box = document.getElementById('payment_split_validation');
        if (box) {
            box.textContent = message;
            box.classList.remove('hidden');
        }
    }

    function hideSplitValidationError() {
        var box = document.getElementById('payment_split_validation');
        if (box) {
            box.classList.add('hidden');
            box.textContent = '';
        }
    }

    function validatePaymentSplits(options) {
        options = options || {};
        hideSplitValidationError();
        var grandTotal = getTargetTotal();
        var paymentStage = String(document.getElementById('payment_stage')?.value || 'final').toLowerCase();

        if (paymentStage === 'zero_advance') {
            return {
                payment_stage: 'zero_advance',
                payment_amount: 0,
                advanceTotal: 0,
                codTotal: grandTotal,
                hasCod: true,
                primaryMode: 'pay_on_pickup',
                primaryTxn: '',
                splits: [{
                    mode: 'pay_on_pickup',
                    amount: grandTotal,
                    transaction_id: ''
                }],
                payment_date: String(document.getElementById('payment_date')?.value || ''),
                payment_note: String(document.getElementById('payment_note')?.value || ''),
            };
        }

        var splits = collectAllPaymentSplitRowsFromUi();
        if (!splits.length) {
            showSplitValidationError('Add at least one payment line.');
            return null;
        }

        var advanceTotal = getPaymentSplitAdvanceTotalFromUi();
        var codTotal = getPaymentSplitCodTotalFromUi();
        var paymentAmount = getPaymentSplitTotalFromUi();
        var hasCod = codTotal > 0.001;

        var isWaivedAllowed = typeof window.isPosFollowUpWaivedCheckout === 'function' && window.isPosFollowUpWaivedCheckout();

        for (var i = 0; i < splits.length; i++) {
            if (splits[i].mode === 'waived') {
                if (!isWaivedAllowed) {
                    showSplitValidationError('Waived payment mode is only allowed for Reship or Replacement follow-up orders.');
                    return null;
                }
                if (isFinite(splits[i].amount) && splits[i].amount > 0.001) {
                    showSplitValidationError('Waived payment line must be zero amount.');
                    return null;
                }
                continue;
            }
            if (!isFinite(splits[i].amount) || splits[i].amount <= 0) {
                showSplitValidationError('Each payment line must have amount greater than zero.');
                return null;
            }
        }

        var allWaived = splits.every(function (s) { return s.mode === 'waived'; });
        if (allWaived) {
            if (!isWaivedAllowed) {
                showSplitValidationError('Waived payment mode is only allowed for Reship or Replacement follow-up orders.');
                return null;
            }
            var primaryWaived = splits[0] || { mode: 'waived', transaction_id: '' };
            return {
                splits: splits,
                total: 0,
                advanceTotal: 0,
                codTotal: 0,
                paymentStage: 'final',
                hasCod: false,
                primaryMode: 'waived',
                primaryTxn: primaryWaived.transaction_id || '',
            };
        }

        if (hasCod) {
            var isPayOnPickup = splits.some(function (s) { return s.mode === 'pay_on_pickup'; });
            var pendingLabel = isPayOnPickup ? 'Advance plus Pay on Pickup' : 'Advance plus COD';
            if (paymentAmount + 0.02 < grandTotal) {
                showSplitValidationError(pendingLabel + ' must equal order total ₹ ' + grandTotal);
                return null;
            }
            if (paymentAmount - 0.02 > grandTotal) {
                showSplitValidationError(pendingLabel + ' exceeds order total.');
                return null;
            }
            paymentStage = 'advance';
        } else {
            if (paymentAmount <= 0) {
                showSplitValidationError('Payment amount must be greater than zero.');
                return null;
            }
            if (paymentStage === 'final') {
                if (paymentAmount + 0.02 < grandTotal) {
                    showSplitValidationError('Final payment must be FULL amount ₹ ' + grandTotal);
                    return null;
                }
                if (paymentAmount - 0.02 > grandTotal) {
                    showSplitValidationError('Over payment not allowed');
                    return null;
                }
            } else if (paymentStage === 'partial' || paymentStage === 'advance') {
                if (grandTotal > 0 && paymentAmount + 0.02 >= grandTotal) {
                    showSplitValidationError('Partial payment must be less than total ₹ ' + grandTotal);
                    return null;
                }
            }
        }

        var container = getPaymentSplitRowsContainer();
        for (var j = 0; j < splits.length; j++) {
            var s = splits[j];
            if ((s.mode === 'razorpay' || s.mode === 'cheque') && !s.transaction_id) {
                showSplitValidationError((s.mode === 'cheque' ? 'Cheque number' : 'Transaction ID') + ' is required for ' + s.mode + ' (line ' + (j + 1) + ').');
                var rows = container ? container.querySelectorAll('.payment-split-row') : [];
                var row = rows[j];
                var txnInput = row ? row.querySelector('.payment-split-txn') : null;
                if (txnInput) {
                    txnInput.focus();
                }
                return null;
            }
        }

        var highValueLimit = config.highValueLimit || 200000;
        var cashLegNeeds269 = splits.some(function (s) {
            return s.mode === 'cash' && s.amount + 0.02 >= highValueLimit;
        });
        if (cashLegNeeds269 && !options.skip269stConfirm) {
            if (typeof window.showPosConfirmModal === 'function') {
                window.showPosConfirmModal({
                    title: 'Section 269ST Cash Warning',
                    message: 'Cash receipts of ₹2,00,000 or more are restricted under Income Tax Act Section 269ST. Please switch to digital payment.\n\nDo you still want to continue after acknowledging this warning?',
                    confirmText: 'Acknowledge & Continue',
                    cancelText: 'Switch Payment',
                    tone: 'warning',
                    onConfirm: function () {
                        if (typeof options.on269stConfirmed === 'function') {
                            options.on269stConfirmed();
                        }
                    },
                    onCancel: function () {
                        showSplitValidationError('Please switch to digital payment or acknowledge the cash warning.');
                    }
                });
                return null;
            }
        }

        return {
            payment_stage: paymentStage,
            payment_amount: hasCod ? advanceTotal : paymentAmount,
            payment_splits: splits,
            payment_date: String(document.getElementById('payment_date')?.value || ''),
            payment_note: String(document.getElementById('payment_note')?.value || ''),
        };
    }

    function syncPaymentDatePickerMax() {
        var el = document.getElementById('payment_date');
        if (!el) {
            return;
        }
        var t = posPaymentDateLocalYmd();
        el.max = t;
        if (el.value && el.value > t) {
            el.value = t;
        }
    }

    function bindUi() {
        var addBtn = document.getElementById('payment_split_add_btn');
        if (addBtn && addBtn.dataset.bound !== '1') {
            addBtn.dataset.bound = '1';
            addBtn.addEventListener('click', function () {
                addPaymentSplitRow('cash', '', '');
            });
        }

        var stageEl = document.getElementById('payment_stage');
        if (stageEl && stageEl.dataset.bound !== '1') {
            stageEl.dataset.bound = '1';
            stageEl.addEventListener('change', recalcPaymentSplitUi);
        }

        var paymentDateInput = document.getElementById('payment_date');
        if (paymentDateInput && paymentDateInput.dataset.bound !== '1') {
            paymentDateInput.dataset.bound = '1';
            paymentDateInput.addEventListener('input', function () {
                var t = posPaymentDateLocalYmd();
                if (paymentDateInput.value && paymentDateInput.value > t) {
                    paymentDateInput.value = t;
                }
            });
        }

        var submitBtn = document.getElementById(config.submitButtonId);
        if (submitBtn && submitBtn.dataset.bound !== '1') {
            submitBtn.dataset.bound = '1';
            submitBtn.addEventListener('click', function () {
                var payInfo = validatePaymentSplits();
                if (!payInfo) {
                    return;
                }
                var payDateEl = document.getElementById('payment_date');
                if (payDateEl && payDateEl.value) {
                    var todayYmd = posPaymentDateLocalYmd();
                    if (payDateEl.value > todayYmd) {
                        showSplitValidationError('Payment date cannot be in the future.');
                        payDateEl.value = todayYmd;
                        payDateEl.focus();
                        return;
                    }
                }
                if (typeof config.onSubmit === 'function') {
                    config.onSubmit(payInfo);
                }
            });
        }
    }

    function openModal(targetTotal) {
        var modal = document.getElementById('paymentModal');
        if (!modal) {
            return;
        }
        hideSplitValidationError();
        syncPaymentDatePickerMax();
        document.getElementById('payment_stage').value = 'final';
        document.getElementById('payment_note').value = '';
        document.getElementById('payment_date').value = posPaymentDateLocalYmd();
        resetPaymentSplitRows(typeof targetTotal === 'number' ? targetTotal : getTargetTotal());
        recalcPaymentSplitUi();
        modal.classList.remove('hidden');
    }

    function closeModal() {
        var modal = document.getElementById('paymentModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        hideSplitValidationError();
    }

    function init(options) {
        config = Object.assign({}, config, options || {});
        bindUi();
    }

    window.PosPaymentSplit = {
        init: init,
        openModal: openModal,
        closeModal: closeModal,
        validatePaymentSplits: validatePaymentSplits,
        recalcPaymentSplitUi: recalcPaymentSplitUi,
        resetPaymentSplitRows: resetPaymentSplitRows,
        getTargetTotal: getTargetTotal,
        showSplitValidationError: showSplitValidationError,
        hideSplitValidationError: hideSplitValidationError,
    };
})(window);
