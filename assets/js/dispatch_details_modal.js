/**
 * Common Dispatch Details Modal JS Logic
 * Supports adding and editing dispatch details on both posinvoice & dispatch pages.
 */

(function () {
    let cdmCurrentInvoiceId = 0;
    let cdmCurrentInvoiceNumber = '';
    let cdmCurrentOrderNumber = '';
    let cdmDispatchesList = [];
    let cdmActiveDispatchId = 0;

    function getElement(id) {
        return document.getElementById(id);
    }

    function formatDatetimeForInput(dtStr) {
        if (!dtStr) return '';
        const d = new Date(dtStr);
        if (isNaN(d.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        const yyyy = d.getFullYear();
        const mm = pad(d.getMonth() + 1);
        const dd = pad(d.getDate());
        const hh = pad(d.getHours());
        const min = pad(d.getMinutes());
        return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
    }

    function cdmShowAlert(message, type = 'error') {
        const banner = getElement('cdmAlertBanner');
        if (!banner) return;
        banner.className = `mx-6 mt-4 p-3 rounded-lg border text-xs font-medium ${
            type === 'success'
                ? 'bg-green-50 border-green-200 text-green-800'
                : type === 'warning'
                ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                : 'bg-red-50 border-red-200 text-red-800'
        }`;
        banner.textContent = message;
        banner.classList.remove('hidden');
    }

    function cdmHideAlert() {
        const banner = getElement('cdmAlertBanner');
        if (banner) banner.classList.add('hidden');
    }

    function cdmResetForm() {
        const form = getElement('commonDispatchForm');
        if (form) form.reset();

        cdmHideAlert();

        const testTracking = getElement('cdmTestTrackingUrl');
        if (testTracking) testTracking.classList.add('hidden');

        const testLabel = getElement('cdmTestLabelUrl');
        if (testLabel) testLabel.classList.add('hidden');

        const badge = getElement('cdmBadgeStatus');
        if (badge) badge.classList.add('hidden');

        const inputInvoiceId = getElement('cdmInputInvoiceId');
        if (inputInvoiceId) inputInvoiceId.value = cdmCurrentInvoiceId;

        const inputDispatchId = getElement('cdmInputDispatchId');
        if (inputDispatchId) inputDispatchId.value = '0';

        const inputOrderNumber = getElement('cdmInputOrderNumber');
        if (inputOrderNumber) inputOrderNumber.value = cdmCurrentOrderNumber || '';

        const inputDispatchDate = getElement('cdmInputDispatchDate');
        if (inputDispatchDate) inputDispatchDate.value = formatDatetimeForInput(new Date());

        const inputShipmentStatus = getElement('cdmInputShipmentStatus');
        if (inputShipmentStatus) inputShipmentStatus.value = 'Ready to Ship';

        const inputBoxNo = getElement('cdmInputBoxNo');
        if (inputBoxNo) inputBoxNo.value = '1';
    }

    function cdmPopulateForm(d) {
        cdmResetForm();

        if (!d) return;

        cdmActiveDispatchId = parseInt(d.id || 0, 10);

        const inputInvoiceId = getElement('cdmInputInvoiceId');
        if (inputInvoiceId) inputInvoiceId.value = d.invoice_id || cdmCurrentInvoiceId;

        const inputDispatchId = getElement('cdmInputDispatchId');
        if (inputDispatchId) inputDispatchId.value = cdmActiveDispatchId;

        const inputOrderNumber = getElement('cdmInputOrderNumber');
        if (inputOrderNumber) inputOrderNumber.value = d.order_number || cdmCurrentOrderNumber || '';

        const inputCourierName = getElement('cdmInputCourierName');
        if (inputCourierName) inputCourierName.value = d.courier_name || '';

        const inputAwbCode = getElement('cdmInputAwbCode');
        if (inputAwbCode) inputAwbCode.value = d.awb_code || '';

        const inputExoticShipmentId = getElement('cdmInputExoticShipmentId');
        if (inputExoticShipmentId) inputExoticShipmentId.value = d.exotic_shipment_id || '';

        const inputTrackingUrl = getElement('cdmInputTrackingUrl');
        if (inputTrackingUrl) inputTrackingUrl.value = d.tracking_url || '';

        const testTracking = getElement('cdmTestTrackingUrl');
        if (testTracking && d.tracking_url) {
            testTracking.href = d.tracking_url;
            testTracking.classList.remove('hidden');
        }

        const inputLabelUrl = getElement('cdmInputLabelUrl');
        if (inputLabelUrl) inputLabelUrl.value = d.label_url || '';

        const testLabel = getElement('cdmTestLabelUrl');
        if (testLabel && d.label_url) {
            testLabel.href = d.label_url;
            testLabel.classList.remove('hidden');
        }

        const inputPickupLocation = getElement('cdmInputPickupLocation');
        if (inputPickupLocation) inputPickupLocation.value = d.pickup_location || '';

        const inputDispatchDate = getElement('cdmInputDispatchDate');
        if (inputDispatchDate) inputDispatchDate.value = formatDatetimeForInput(d.dispatch_date);

        const inputShipmentStatus = getElement('cdmInputShipmentStatus');
        if (inputShipmentStatus && d.shipment_status) {
            inputShipmentStatus.value = d.shipment_status;
        }

        const inputBoxNo = getElement('cdmInputBoxNo');
        if (inputBoxNo) inputBoxNo.value = d.box_no || '1';

        const inputBoxSize = getElement('cdmInputBoxSize');
        if (inputBoxSize) inputBoxSize.value = d.box_size || '';

        const inputWeight = getElement('cdmInputWeight');
        if (inputWeight) inputWeight.value = d.weight !== null && d.weight !== undefined ? d.weight : '';

        const inputShippingCharges = getElement('cdmInputShippingCharges');
        if (inputShippingCharges) inputShippingCharges.value = d.shipping_charges !== null && d.shipping_charges !== undefined ? d.shipping_charges : '';

        const inputBatchNo = getElement('cdmInputBatchNo');
        if (inputBatchNo) inputBatchNo.value = d.batch_no || '';

        const badge = getElement('cdmBadgeStatus');
        if (badge && d.shipment_status) {
            badge.textContent = d.shipment_status;
            badge.classList.remove('hidden');
        }
    }

    function cdmRenderBoxesTabs() {
        const tabsWrap = getElement('cdmBoxesTabsWrap');
        const pillsContainer = getElement('cdmBoxesPills');
        if (!tabsWrap || !pillsContainer) return;

        pillsContainer.innerHTML = '';

        if (!cdmDispatchesList || cdmDispatchesList.length <= 1) {
            tabsWrap.classList.add('hidden');
            return;
        }

        tabsWrap.classList.remove('hidden');

        cdmDispatchesList.forEach((item, index) => {
            const isSelected = parseInt(item.id, 10) === cdmActiveDispatchId || (cdmActiveDispatchId === 0 && index === 0);
            const boxLabel = item.box_no ? `Box ${item.box_no}` : `Dispatch #${item.id}`;
            const subText = item.courier_name ? ` (${item.courier_name})` : '';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `px-3 py-1 text-xs font-semibold rounded-lg transition ${
                isSelected
                    ? 'bg-orange-600 text-white shadow-sm'
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
            }`;
            btn.textContent = boxLabel + subText;
            btn.addEventListener('click', () => {
                cdmPopulateForm(item);
                cdmRenderBoxesTabs();
            });
            pillsContainer.appendChild(btn);
        });
    }

    window.cdmPrepareAddNewBox = function () {
        cdmActiveDispatchId = 0;
        cdmResetForm();
        const nextBoxNo = cdmDispatchesList.length + 1;
        const inputBoxNo = getElement('cdmInputBoxNo');
        if (inputBoxNo) inputBoxNo.value = String(nextBoxNo);
        cdmRenderBoxesTabs();
    };

    window.openCommonDispatchModal = function (params) {
        params = params || {};
        cdmCurrentInvoiceId = parseInt(params.invoice_id || 0, 10);
        cdmCurrentInvoiceNumber = String(params.invoice_number || '').trim();
        cdmCurrentOrderNumber = String(params.order_number || '').trim();
        cdmActiveDispatchId = parseInt(params.dispatch_id || 0, 10);
        cdmDispatchesList = [];

        if (!cdmCurrentInvoiceId && !cdmActiveDispatchId) {
            if (typeof window.showPosMessageModal === 'function') {
                window.showPosMessageModal({
                    title: 'Invalid Invoice',
                    message: 'Please select a valid invoice to add dispatch details.',
                    tone: 'warning'
                });
            } else {
                alert('Please select a valid invoice.');
            }
            return;
        }

        const modal = getElement('commonDispatchModal');
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const elInv = getElement('cdmInvoiceNum');
        if (elInv) elInv.textContent = cdmCurrentInvoiceNumber || cdmCurrentInvoiceId || '-';

        const elOrdWrap = getElement('cdmOrderNumWrap');
        const elOrd = getElement('cdmOrderNum');
        if (cdmCurrentOrderNumber) {
            if (elOrd) elOrd.textContent = cdmCurrentOrderNumber;
            if (elOrdWrap) elOrdWrap.classList.remove('hidden');
        } else {
            if (elOrdWrap) elOrdWrap.classList.add('hidden');
        }

        const spinner = getElement('cdmLoadingSpinner');
        const form = getElement('commonDispatchForm');
        if (spinner) spinner.classList.remove('hidden');
        if (form) form.classList.add('hidden');

        cdmResetForm();

        let fetchUrl = `?page=dispatch&action=get_dispatch_details_ajax&invoice_id=${cdmCurrentInvoiceId}`;
        if (cdmActiveDispatchId > 0) {
            fetchUrl = `?page=dispatch&action=get_dispatch_details_ajax&dispatch_id=${cdmActiveDispatchId}`;
        }

        fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((res) => res.json())
            .then((data) => {
                if (spinner) spinner.classList.add('hidden');
                if (form) form.classList.remove('hidden');

                if (data && data.success) {
                    cdmDispatchesList = Array.isArray(data.dispatches) ? data.dispatches : [];
                    if (data.data) {
                        if (!cdmCurrentOrderNumber && data.data.order_number) {
                            cdmCurrentOrderNumber = data.data.order_number;
                            if (elOrd) elOrd.textContent = cdmCurrentOrderNumber;
                            if (elOrdWrap) elOrdWrap.classList.remove('hidden');
                        }
                        cdmPopulateForm(data.data);
                    } else if (cdmDispatchesList.length > 0) {
                        cdmPopulateForm(cdmDispatchesList[0]);
                    }
                    cdmRenderBoxesTabs();
                } else {
                    cdmResetForm();
                }
            })
            .catch(() => {
                if (spinner) spinner.classList.add('hidden');
                if (form) form.classList.remove('hidden');
                cdmResetForm();
            });
    };

    window.closeCommonDispatchModal = function () {
        const modal = getElement('commonDispatchModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        cdmResetForm();
    };

    document.addEventListener('DOMContentLoaded', () => {
        const trackingInput = getElement('cdmInputTrackingUrl');
        if (trackingInput) {
            trackingInput.addEventListener('input', function () {
                const testLink = getElement('cdmTestTrackingUrl');
                if (testLink) {
                    if (this.value.trim()) {
                        testLink.href = this.value.trim();
                        testLink.classList.remove('hidden');
                    } else {
                        testLink.classList.add('hidden');
                    }
                }
            });
        }

        const labelInput = getElement('cdmInputLabelUrl');
        if (labelInput) {
            labelInput.addEventListener('input', function () {
                const testLabel = getElement('cdmTestLabelUrl');
                if (testLabel) {
                    if (this.value.trim()) {
                        testLabel.href = this.value.trim();
                        testLabel.classList.remove('hidden');
                    } else {
                        testLabel.classList.add('hidden');
                    }
                }
            });
        }

        const form = getElement('commonDispatchForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const courierNameInput = getElement('cdmInputCourierName');
                if (!courierNameInput || !courierNameInput.value.trim()) {
                    cdmShowAlert('Courier Name is required.', 'error');
                    return;
                }

                cdmHideAlert();

                const saveBtn = getElement('cdmSaveBtn');
                let oldBtnHtml = '';
                if (saveBtn) {
                    saveBtn.disabled = true;
                    oldBtnHtml = saveBtn.innerHTML;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Saving…';
                }

                const formData = new FormData(form);
                const payload = {};
                formData.forEach((value, key) => {
                    payload[key] = value;
                });

                fetch('?page=dispatch&action=save_dispatch_details_ajax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = oldBtnHtml;
                        }

                        if (data && data.success) {
                            closeCommonDispatchModal();

                            const successMsg = data.message || 'Dispatch details saved successfully.';
                            if (typeof window.showPosMessageModal === 'function') {
                                window.showPosMessageModal({
                                    title: 'Dispatch Saved',
                                    message: successMsg,
                                    tone: 'success'
                                });
                            }

                            // Refresh table depending on page context
                            if (typeof window.loadInvoices === 'function') {
                                window.loadInvoices();
                            } else {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 600);
                            }
                        } else {
                            cdmShowAlert((data && data.message) ? data.message : 'Failed to save dispatch details.', 'error');
                        }
                    })
                    .catch((err) => {
                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = oldBtnHtml;
                        }
                        cdmShowAlert((err && err.message) ? err.message : 'Network error. Could not save dispatch details.', 'error');
                    });
            });
        }
    });
})();
