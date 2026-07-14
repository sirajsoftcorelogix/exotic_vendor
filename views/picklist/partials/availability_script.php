<script>
(function() {
    function setItemAvailability(itemId, status, onSuccess) {
        if (!itemId || !status) return Promise.resolve(false);

        var isPartial = status === 'partially_available';
        var title = isPartial ? 'Mark partially available?' : 'Mark not available?';
        var message = isPartial
            ? 'Mark this item as partially available? The order status will not change.'
            : 'Mark this item as not available? The order status will not change.';
        var confirmText = isPartial ? 'Yes, mark partial' : 'Yes, mark not available';

        var confirmFn = window.openPicklistConfirmModal;
        var confirmPromise = confirmFn
            ? confirmFn({
                title: title,
                message: message,
                confirmText: confirmText,
                cancelText: 'Cancel'
            })
            : Promise.resolve(window.confirm(message));

        return confirmPromise.then(function(confirmed) {
            if (!confirmed) return false;

            var fd = new FormData();
            fd.append('item_id', itemId);
            fd.append('status', status);

            return fetch('index.php?page=picklist&action=set_item_availability', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (typeof onSuccess === 'function') {
                            onSuccess(data);
                        } else {
                            window.location.reload();
                        }
                        return true;
                    }
                    showAlert(data.message || 'Failed to update availability.', 'error');
                    return false;
                })
                .catch(function() {
                    showAlert('Network error.', 'error');
                    return false;
                });
        });
    }

    window.picklistSetItemAvailability = setItemAvailability;

    document.querySelectorAll('.js-picklist-set-availability').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var itemId = btn.getAttribute('data-item-id');
            var status = btn.getAttribute('data-status');
            btn.disabled = true;
            setItemAvailability(itemId, status, function() {
                window.location.reload();
            }).finally(function() {
                btn.disabled = false;
            });
        });
    });
})();
</script>
