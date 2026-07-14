<?php
require_once __DIR__ . '/../../../helpers/payment_type_groups.php';
?>
document.addEventListener('DOMContentLoaded', function() {
    const paymentTypeSelect = document.querySelector('#payment_type');
    if (!paymentTypeSelect || typeof window.jQuery === 'undefined') {
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

    $paymentTypeSelect.select2({
        placeholder: 'Select Payment Type',
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
