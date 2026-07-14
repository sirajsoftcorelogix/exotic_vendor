<script>
(function() {
    var revertIconWrapClass = 'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-600 shadow-sm';
    var revertIconHtml = '<i class="fas fa-undo text-2xl" aria-hidden="true"></i>';
    var revertOkClass = 'inline-flex min-w-[6rem] items-center justify-center rounded-xl bg-gradient-to-b from-amber-600 to-amber-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-amber-900/15 hover:from-amber-700 hover:to-amber-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition';

    function unpickPicklistItem(itemId, onSuccess) {
        if (!itemId) return Promise.resolve(false);

        var confirmFn = window.openPicklistConfirmModal;
        var confirmPromise = confirmFn
            ? confirmFn({
                title: 'Revert pick?',
                message: 'Mark this item as not picked? The order status will be set back to Added to Picklist.',
                confirmText: 'Yes, revert pick',
                cancelText: 'Cancel',
                iconWrapClass: revertIconWrapClass,
                iconHtml: revertIconHtml,
                okClass: revertOkClass
            })
            : Promise.resolve(window.confirm('Mark this item as not picked?'));

        return confirmPromise.then(function(confirmed) {
            if (!confirmed) return false;

            var fd = new FormData();
            fd.append('item_id', itemId);

            return fetch('index.php?page=picklist&action=unpick_item', { method: 'POST', body: fd })
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
                    showAlert(data.message || 'Failed to revert pick.', 'error');
                    return false;
                })
                .catch(function() {
                    showAlert('Network error.', 'error');
                    return false;
                });
        });
    }

    window.picklistUnpickItem = unpickPicklistItem;

    document.querySelectorAll('.js-picklist-unpick-item').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var itemId = btn.getAttribute('data-item-id');
            btn.disabled = true;
            unpickPicklistItem(itemId, function() {
                window.location.reload();
            }).finally(function() {
                btn.disabled = false;
            });
        });
    });
})();
</script>