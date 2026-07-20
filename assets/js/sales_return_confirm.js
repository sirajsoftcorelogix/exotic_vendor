(function () {
    'use strict';

    function getConfirmFn() {
        return (typeof window.customConfirm === 'function')
            ? window.customConfirm
            : function (msg) {
                return Promise.resolve(window.confirm(msg));
            };
    }

    function buildMessage(orderNumber) {
        var suffix = orderNumber ? (' for order ' + orderNumber) : '';
        return 'Create a sales return' + suffix + '? Returned items will be added back to stock when a prior sale OUT exists.';
    }

    window.confirmSalesReturnNavigate = function (url, orderNumber) {
        if (!url) {
            return Promise.resolve(false);
        }

        return getConfirmFn()(
            buildMessage(orderNumber || ''),
            { title: 'Start sales return?', okText: 'Continue', cancelText: 'Cancel' }
        ).then(function (confirmed) {
            if (confirmed) {
                window.location.href = url;
            }
            return confirmed;
        });
    };

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-sales-return-create]');
        if (!trigger) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        var url = trigger.getAttribute('data-sales-return-url')
            || trigger.getAttribute('href')
            || '';
        var orderNumber = trigger.getAttribute('data-order-number') || '';

        window.confirmSalesReturnNavigate(url, orderNumber);
    }, true);
})();
