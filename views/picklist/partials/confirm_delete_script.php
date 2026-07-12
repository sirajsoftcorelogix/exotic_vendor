<script>
(function() {
    document.querySelectorAll('.js-picklist-confirm-action').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                return;
            }

            var itemId = el.getAttribute('data-item-id');
            if (itemId) {
                var fd = new FormData();
                fd.append('item_id', itemId);
                el.setAttribute('disabled', 'disabled');

                fetch('index.php?page=picklist&action=delete_item', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            if (data.picklist_deleted) {
                                window.location.href = data.redirect || 'index.php?page=picklist&action=list';
                            } else {
                                window.location.reload();
                            }
                        } else {
                            showAlert(data.message || 'Could not remove item.', 'error');
                            el.removeAttribute('disabled');
                        }
                    })
                    .catch(function() {
                        showAlert('Network error.', 'error');
                        el.removeAttribute('disabled');
                    });
                return;
            }

            var href = el.getAttribute('href');
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                window.location.href = href;
            }
        });
    });
})();
</script>
