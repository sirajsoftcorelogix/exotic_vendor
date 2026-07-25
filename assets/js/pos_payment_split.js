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

    function formatPaymentInr(amount) {
        var n = parseFloat(String(amount));
        if (!isFinite(n)) {
            n = 0;
        }
        try {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(n);
        } catch (e) {
            return '₹ ' + n.toFixed(2);
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
        options.forEach(function (pair) {
            if (!Array.isArray(pair) || !pair[0]) {
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
        var total = parseFloat(String(grandTotal));
        addPaymentSplitRow('cash', isFinite(total) && total > 0 ? total : '', '');
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
            if (!mode || !isFinite(amount) || amount <= 0) {
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

    function getPaymentSplitAdvanceTotalFromUi() {
        var total = 0;
        collectAllPaymentSplitRowsFromUi().forEach(function (s) {
            if (s.mode !== 'cod') {
                total += s.amount;
            }
        });
        return Math.round(total * 100) / 100;
    }

    function getPaymentSplitCodTotalFromUi() {
        var total = 0;
        collectAllPaymentSplitRowsFromUi().forEach(function (s) {
            if (s.mode === 'cod') {
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

    function recalcPaymentSplitUi() {
        var splits = collectAllPaymentSplitRowsFromUi();
        var splitTotal = getPaymentSplitTotalFromUi();
        var advanceTotal = getPaymentSplitAdvanceTotalFromUi();
        var codTotal = getPaymentSplitCodTotalFromUi();
        var hasCod = paymentSplitHasCodFromUi();
        var orderTotal = getTargetTotal();
        var displayTotal = getDisplayOrderTotal();
        var stage = String(document.getElementById('payment_stage')?.value || 'final').toLowerCase();
        var balance = Math.round((orderTotal - splitTotal) * 100) / 100;

        syncLegacyPaymentHiddenFields(splits, hasCod ? advanceTotal : splitTotal);

        var orderEl = document.getElementById('payment_summary_order');
        var paidEl = document.getElementById('payment_summary_paid');
        var balEl = document.getElementById('payment_summary_balance');
        var balLabelEl = document.getElementById('payment_summary_balance_label');
        var hintEl = document.getElementById('payment_summary_hint');
        var countEl = document.getElementById('payment_split_count');
        var totalEl = document.getElementById('payment_split_total');

        if (orderEl) {
            orderEl.textContent = formatPaymentInr(displayTotal);
        }
        if (paidEl) {
            paidEl.textContent = formatPaymentInr(hasCod ? advanceTotal : splitTotal);
        }
        if (balLabelEl) {
            balLabelEl.textContent = hasCod ? 'COD pending' : 'Balance';
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
                    hintEl.textContent = formatPaymentInr(codTotal) + ' will be collected on delivery.';
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

    function validatePaymentSplits() {
        hideSplitValidationError();
        var grandTotal = getTargetTotal();
        var splits = collectAllPaymentSplitRowsFromUi();
        if (!splits.length) {
            showSplitValidationError('Add at least one payment line.');
            return null;
        }

        var paymentStage = String(document.getElementById('payment_stage')?.value || 'final').toLowerCase();
        var advanceTotal = getPaymentSplitAdvanceTotalFromUi();
        var codTotal = getPaymentSplitCodTotalFromUi();
        var paymentAmount = getPaymentSplitTotalFromUi();
        var hasCod = codTotal > 0.001;

        for (var i = 0; i < splits.length; i++) {
            if (!isFinite(splits[i].amount) || splits[i].amount <= 0) {
                showSplitValidationError('Each payment line must have amount greater than zero.');
                return null;
            }
        }

        if (hasCod) {
            if (paymentAmount + 0.02 < grandTotal) {
                showSplitValidationError('Advance plus COD must equal order total ₹ ' + grandTotal);
                return null;
            }
            if (paymentAmount - 0.02 > grandTotal) {
                showSplitValidationError('Advance plus COD exceeds order total.');
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
        if (cashLegNeeds269) {
            var okCash = window.confirm('Cash receipts of ₹2,00,000 or more are restricted under Income Tax Act Section 269ST. Please switch to digital payment.\n\nDo you still want to continue after acknowledging this warning?');
            if (!okCash) {
                showSplitValidationError('Please switch to digital payment or acknowledge the cash warning.');
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
