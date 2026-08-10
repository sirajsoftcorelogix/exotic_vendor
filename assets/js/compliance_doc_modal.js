/**
 * Central High-Value Transaction Compliance Modal Component
 *
 * Prompts user/cashier for missing PAN Card, Passport Number, Country of Residence, or GSTIN
 * when generating an invoice for transactions >= ₹2,00,000.
 */
(function (window, document) {
    'use strict';

    if (window.ComplianceDocModal) {
        return;
    }

    const MODAL_ID = 'compliance_doc_central_modal';

    function injectModalMarkup() {
        if (document.getElementById(MODAL_ID)) {
            return;
        }

        const div = document.createElement('div');
        div.id = MODAL_ID;
        div.className = 'fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4';
        div.innerHTML = `
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl transition-all dark:bg-slate-800 border border-amber-200 dark:border-amber-900">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">High-Value Invoice Compliance</h3>
                            <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Pan / Passport required for invoices &ge; ₹2,00,000</p>
                        </div>
                    </div>
                    <button type="button" id="compliance_doc_modal_close" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div id="compliance_doc_alert" class="p-3 text-xs rounded-xl bg-amber-50 text-amber-800 border border-amber-200">
                        Invoice total exceeds statutory limit. Please supply missing tax compliance details before proceeding.
                    </div>

                    <form id="compliance_doc_form" onsubmit="return false;">
                        <input type="hidden" id="compliance_doc_customer_id" value="" />

                        <div class="space-y-3 text-sm">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 text-xs mb-1">Customer Residency Status *</label>
                                <select id="compliance_doc_residency" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="INDIAN_RESIDENT">Indian Resident</option>
                                    <option value="NRI">NRI (Non-Resident Indian)</option>
                                    <option value="FOREIGN_NATIONAL">Foreign National</option>
                                </select>
                            </div>

                            <div id="compliance_field_gstin_wrap">
                                <label class="block font-medium text-slate-700 dark:text-slate-300 text-xs mb-1">GSTIN (B2B Tax Invoice)</label>
                                <input type="text" id="compliance_doc_gstin" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-sm uppercase" placeholder="e.g. 07AAAAA0000A1Z5" maxLength="15" />
                            </div>

                            <div id="compliance_field_pan_wrap">
                                <label class="block font-medium text-slate-700 dark:text-slate-300 text-xs mb-1">PAN Card Number <span id="compliance_pan_req_star" class="text-red-500">*</span></label>
                                <input type="text" id="compliance_doc_pan" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-sm uppercase" placeholder="e.g. ABCDE1234F" maxLength="10" />
                            </div>

                            <div id="compliance_field_passport_wrap" class="hidden space-y-3">
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 text-xs mb-1">Passport Number *</label>
                                    <input type="text" id="compliance_doc_passport" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-sm uppercase" placeholder="e.g. A1234567" />
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 text-xs mb-1">Country of Residence *</label>
                                    <select id="compliance_doc_country" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-sm">
                                        <option value="">Select Country of Residence</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end space-x-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                            <button type="button" id="compliance_doc_cancel_btn" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-xl border border-slate-300">
                                Cancel
                            </button>
                            <button type="submit" id="compliance_doc_save_btn" class="px-5 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md transition-all flex items-center space-x-2">
                                <span>Save &amp; Generate Invoice</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(div);

        // Bind events
        document.getElementById('compliance_doc_modal_close').addEventListener('click', hideModal);
        document.getElementById('compliance_doc_cancel_btn').addEventListener('click', hideModal);
        document.getElementById('compliance_doc_residency').addEventListener('change', updateFieldVisibility);
        document.getElementById('compliance_doc_gstin').addEventListener('input', function () {
            const val = this.value.trim().toUpperCase();
            if (val.length === 15 && /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(val)) {
                const panPan = val.substring(2, 12);
                document.getElementById('compliance_doc_pan').value = panPan;
            }
        });

        document.getElementById('compliance_doc_form').addEventListener('submit', handleSave);
    }

    function updateFieldVisibility() {
        const residency = document.getElementById('compliance_doc_residency').value;
        const panWrap = document.getElementById('compliance_field_pan_wrap');
        const passportWrap = document.getElementById('compliance_field_passport_wrap');
        const panStar = document.getElementById('compliance_pan_req_star');

        if (residency === 'INDIAN_RESIDENT') {
            panWrap.classList.remove('hidden');
            passportWrap.classList.add('hidden');
            panStar.classList.remove('hidden');
        } else if (residency === 'NRI') {
            panWrap.classList.remove('hidden');
            passportWrap.classList.remove('hidden');
            panStar.classList.add('hidden'); // Optional if Passport provided
        } else if (residency === 'FOREIGN_NATIONAL') {
            panWrap.classList.add('hidden');
            passportWrap.classList.remove('hidden');
        }
    }

    function populateCountryOptions(selectedVal) {
        const selectEl = document.getElementById('compliance_doc_country');
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">Select Country of Residence</option>';
        const list = window.POS_COUNTRY_LIST || { IN: 'India', US: 'United States', AE: 'United Arab Emirates', GB: 'United Kingdom', CA: 'Canada', AU: 'Australia' };
        const upper = (selectedVal || '').trim().toUpperCase();
        Object.keys(list).forEach(function (code) {
            const name = list[code];
            const opt = document.createElement('option');
            opt.value = code;
            opt.textContent = name;
            if (code === upper || name.toUpperCase() === upper) {
                opt.selected = true;
            }
            selectEl.appendChild(opt);
        });
    }

    let activeSuccessCallback = null;
    let activeCancelCallback = null;

    function showModal(options) {
        injectModalMarkup();

        const opts = options || {};
        document.getElementById('compliance_doc_customer_id').value = opts.customerId || '';
        const countryUpper = (opts.country || '').trim().toUpperCase();
        const isIndia = countryUpper === '' || countryUpper === 'IN' || countryUpper === 'IND' || countryUpper === 'INDIA';
        const residency = isIndia ? 'INDIAN_RESIDENT' : (opts.residencyStatus && opts.residencyStatus !== 'INDIAN_RESIDENT' ? opts.residencyStatus : 'FOREIGN_NATIONAL');
        document.getElementById('compliance_doc_residency').value = residency;
        document.getElementById('compliance_doc_pan').value = opts.pan || '';
        document.getElementById('compliance_doc_passport').value = opts.passport || '';
        document.getElementById('compliance_doc_gstin').value = opts.gstin || '';
        populateCountryOptions(opts.country || '');

        if (opts.message) {
            document.getElementById('compliance_doc_alert').textContent = opts.message;
        }

        updateFieldVisibility();

        activeSuccessCallback = opts.onSuccess || null;
        activeCancelCallback = opts.onCancel || null;

        const modal = document.getElementById(MODAL_ID);
        modal.classList.remove('hidden');
    }

    function hideModal() {
        const modal = document.getElementById(MODAL_ID);
        if (modal) {
            modal.classList.add('hidden');
        }
        if (typeof activeCancelCallback === 'function') {
            activeCancelCallback();
            activeCancelCallback = null;
        }
    }

    function handleSave(e) {
        e.preventDefault();

        const customerId = parseInt(document.getElementById('compliance_doc_customer_id').value, 10) || 0;
        const residency = document.getElementById('compliance_doc_residency').value;
        const pan = document.getElementById('compliance_doc_pan').value.trim().toUpperCase();
        const passport = document.getElementById('compliance_doc_passport').value.trim().toUpperCase();
        const country = document.getElementById('compliance_doc_country').value.trim();
        const gstin = document.getElementById('compliance_doc_gstin').value.trim().toUpperCase();

        if (customerId <= 0) {
            alert('Customer ID is missing.');
            return;
        }

        // Validation
        if (gstin === '') {
            if (residency === 'INDIAN_RESIDENT') {
                if (!/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(pan)) {
                    alert('Please enter a valid 10-character PAN Card number.');
                    return;
                }
            } else if (residency === 'NRI') {
                const hasValidPan = /^[A-Z]{5}[0-9]{4}[A-Z]$/.test(pan);
                const hasValidPassport = passport.length >= 6 && country !== '';
                if (!hasValidPan && !hasValidPassport) {
                    alert('Please provide a valid PAN Card OR Passport Number with Country of Residence for NRI customer.');
                    return;
                }
            } else if (residency === 'FOREIGN_NATIONAL') {
                if (passport.length < 6 || country === '') {
                    alert('Please enter Passport Number and Country of Residence for Foreign National customer.');
                    return;
                }
            }
        }

        const saveBtn = document.getElementById('compliance_doc_save_btn');
        saveBtn.disabled = true;
        saveBtn.innerText = 'Saving...';

        fetch('index.php?page=pos_register&action=save-customer-compliance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                customer_id: customerId,
                customer_residency_status: residency,
                customer_pan: pan,
                passport_number: passport,
                country_of_residence: country,
                confirm_gstin: gstin
            })
        })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Save & Generate Invoice';

                if (data && data.success) {
                    const cb = activeSuccessCallback;
                    hideModal();
                    if (typeof cb === 'function') {
                        cb(data);
                    }
                } else {
                    alert((data && data.message) ? data.message : 'Failed to save compliance details.');
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Save & Generate Invoice';
                alert('An error occurred while saving compliance details.');
                console.error(err);
            });
    }

    window.ComplianceDocModal = {
        open: showModal,
        close: hideModal
    };

})(window, document);
