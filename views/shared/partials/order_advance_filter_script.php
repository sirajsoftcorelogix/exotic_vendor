<?php
require_once __DIR__ . '/../../../helpers/payment_type_groups.php';
?>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.jQuery === 'undefined') {
        return;
    }

    function applyOperatorUi(group) {
        const select = group.dataset.targetSelect ? document.getElementById(group.dataset.targetSelect) : null;
        const badge = group.dataset.badgeId ? document.getElementById(group.dataset.badgeId) : null;
        const isExclude = group.querySelector('input[value="not_in"]')?.checked;

        if (badge) {
            badge.classList.toggle('hidden', !isExclude);
        }
        if (!select) {
            return;
        }

        select.classList.toggle('border-red-300', isExclude);
        select.classList.toggle('border-gray-300', !isExclude);

        const $container = window.jQuery(select).next('.select2-container');
        if ($container.length) {
            $container.find('.select2-selection')
                .toggleClass('border-red-300', isExclude)
                .toggleClass('border-gray-300', !isExclude);
        }
    }

    document.querySelectorAll('[data-filter-operator]').forEach(function(group) {
        group.querySelectorAll('input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                applyOperatorUi(group);
            });
        });
        applyOperatorUi(group);
    });

    document.querySelectorAll('[data-order-filter-select]').forEach(function(select) {
        const isExclude = select.dataset.filterOp === 'not_in';
        const placeholder = isExclude
            ? (select.dataset.placeholderNotIn || 'Exclude…')
            : (select.dataset.placeholderIn || 'Select…');
        let selected = [];

        try {
            selected = JSON.parse(select.dataset.selectedValues || '[]');
        } catch (error) {
            selected = [];
        }

        const $select = window.jQuery(select);
        $select.select2({ placeholder: placeholder, allowClear: true, width: '100%' });
        if (selected.length > 0) {
            $select.val(selected).trigger('change');
        }
    });

    const paymentTypeSelect = document.querySelector('#payment_type');
    if (!paymentTypeSelect) {
        return;
    }

    let paymentTypeGroups = {};
    let preselectedPaymentTypes = [];

    try {
        paymentTypeGroups = JSON.parse(paymentTypeSelect.dataset.paymentTypeGroups || '{}');
        preselectedPaymentTypes = JSON.parse(paymentTypeSelect.dataset.selectedPaymentTypes || '[]');
    } catch (error) {
        paymentTypeGroups = {};
        preselectedPaymentTypes = [];
    }

    const groupPrefix = <?php echo json_encode(PAYMENT_TYPE_GROUP_PREFIX); ?>;

    function isGroupToken(value) {
        return String(value).startsWith(groupPrefix);
    }

    function groupLabelFromToken(value) {
        return String(value).slice(groupPrefix.length);
    }

    function expandPaymentTypeSelection(values) {
        const expanded = new Set();

        (values || []).forEach(function(value) {
            if (isGroupToken(value)) {
                const groupItems = paymentTypeGroups[groupLabelFromToken(value)] || {};
                Object.keys(groupItems).forEach(function(key) {
                    expanded.add(key);
                });
                return;
            }

            if (value) {
                expanded.add(value);
            }
        });

        return Array.from(expanded);
    }

    const $paymentTypeSelect = window.jQuery(paymentTypeSelect);
    const paymentTypeOp = paymentTypeSelect.dataset.paymentTypeOp || 'in';

    $paymentTypeSelect.select2({
        placeholder: paymentTypeOp === 'not_in' ? 'Exclude payment types…' : 'Select Payment Type',
        allowClear: true,
        width: '100%',
        templateResult: function(data) {
            if (!data.id) {
                return data.text;
            }

            if (data.element && data.element.dataset.isGroup === '1') {
                return window.jQuery('<span class="font-semibold text-amber-700"></span>').text(data.text);
            }

            return data.text;
        }
    });

    $paymentTypeSelect.on('select2:select', function() {
        const selected = $paymentTypeSelect.val() || [];
        const expanded = expandPaymentTypeSelection(selected);

        if (expanded.length !== selected.length || selected.some(isGroupToken)) {
            $paymentTypeSelect.val(expanded).trigger('change');
        }
    });

    $paymentTypeSelect.on('select2:unselect', function(event) {
        const value = event.params.data.id;
        if (!isGroupToken(value)) {
            return;
        }

        const groupItems = paymentTypeGroups[groupLabelFromToken(value)] || {};
        const remaining = ($paymentTypeSelect.val() || [])
            .filter(function(item) {
                return !isGroupToken(item) && groupItems[item] === undefined;
            });

        $paymentTypeSelect.val(remaining).trigger('change');
    });

    if (preselectedPaymentTypes.length > 0) {
        $paymentTypeSelect.val(preselectedPaymentTypes).trigger('change');
    }
});
