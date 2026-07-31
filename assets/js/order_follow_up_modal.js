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
            var checked = form.querySelectorAll('input[name="line_ids[]"]:checked');
            if (!checked.length) {
                e.preventDefault();
                if (formError) {
                    formError.textContent = 'Select at least one order line to copy.';
                    formError.classList.remove('hidden');
                }
            }
        });
    }

    setType('copy');
})();
