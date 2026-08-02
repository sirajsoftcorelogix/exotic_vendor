(function () {
    'use strict';

    var modal = document.getElementById('orderFollowUpModal');
    if (!modal) {
        return;
    }

    var form = document.getElementById('orderFollowUpForm');
    var typeInput = document.getElementById('orderFollowUpType');
    var pricingSelect = document.getElementById('orderFollowUpPricingMode');
    var formError = document.getElementById('orderFollowUpFormError');
    var typeButtons = modal.querySelectorAll('[data-follow-up-type]');

    var defaults = {
        reship: 'waived',
        replace: 'same_as_original',
        copy: 'catalog'
    };

    function setType(type) {
        if (!typeInput) {
            return;
        }
        typeInput.value = type;
        typeButtons.forEach(function (btn) {
            var active = btn.getAttribute('data-follow-up-type') === type;
            btn.classList.toggle('border-indigo-600', active);
            btn.classList.toggle('bg-indigo-50', active);
            btn.classList.toggle('font-medium', active);
            btn.classList.toggle('text-indigo-900', active);
        });
        if (pricingSelect && defaults[type]) {
            pricingSelect.value = defaults[type];
        }
        if (pricingSelect) {
            var waivedOpt = pricingSelect.querySelector('option[value="waived"]');
            if (waivedOpt) {
                waivedOpt.hidden = type === 'copy';
                if (type === 'copy' && pricingSelect.value === 'waived') {
                    pricingSelect.value = 'catalog';
                }
            }
        }

        var lineCheckboxes = modal.querySelectorAll('.order-follow-up-line-checkbox');
        var isReshipOrReplace = type === 'reship' || type === 'replace';
        var returnedCount = 0;

        lineCheckboxes.forEach(function (cb) {
            var isReturned = cb.getAttribute('data-is-returned') === '1';
            var label = cb.closest('label');
            if (isReshipOrReplace) {
                if (isReturned) {
                    cb.checked = true;
                    cb.disabled = false;
                    if (label) {
                        label.classList.remove('opacity-40', 'cursor-not-allowed');
                    }
                    returnedCount++;
                } else {
                    cb.checked = false;
                    cb.disabled = true;
                    if (label) {
                        label.classList.add('opacity-40', 'cursor-not-allowed');
                    }
                }
            } else {
                cb.checked = true;
                cb.disabled = false;
                if (label) {
                    label.classList.remove('opacity-40', 'cursor-not-allowed');
                }
            }
        });

        var submitBtn = form ? form.querySelector('button[type="submit"]') : null;
        if (isReshipOrReplace && returnedCount === 0) {
            if (formError) {
                formError.textContent = 'No returned items found in this order for Reship or Replacement.';
                formError.classList.remove('hidden');
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        } else {
            if (formError && formError.textContent.indexOf('No returned items found') !== -1) {
                formError.textContent = '';
                formError.classList.add('hidden');
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    function openModal(type) {
        if (formError) {
            formError.textContent = '';
            formError.classList.add('hidden');
        }
        setType(type || 'copy');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.openOrderFollowUpModal = openModal;

    typeButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setType(btn.getAttribute('data-follow-up-type') || 'copy');
        });
    });

    document.querySelectorAll('[data-open-follow-up]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            openModal(el.getAttribute('data-open-follow-up') || 'copy');
        });
    });

    var closeBtn = document.getElementById('orderFollowUpModalClose');
    var cancelBtn = document.getElementById('orderFollowUpCancel');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            var currentType = typeInput ? typeInput.value : 'copy';
            var checked = form.querySelectorAll('input[name="line_ids[]"]:checked');
            if (!checked.length) {
                e.preventDefault();
                if (formError) {
                    formError.textContent = (currentType === 'reship' || currentType === 'replace')
                        ? 'Select at least one returned order line.'
                        : 'Select at least one order line.';
                    formError.classList.remove('hidden');
                }
                return;
            }

            if (currentType === 'reship' || currentType === 'replace') {
                var hasNonReturned = false;
                checked.forEach(function (cb) {
                    if (cb.getAttribute('data-is-returned') !== '1') {
                        hasNonReturned = true;
                    }
                });
                if (hasNonReturned) {
                    e.preventDefault();
                    if (formError) {
                        formError.textContent = 'Only returned items can be reshipped or replaced.';
                        formError.classList.remove('hidden');
                    }
                    return;
                }
            }
        });
    }

    setType('copy');
})();
