<script>
(function() {
    var selectAll = document.getElementById('picklist-select-all');
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.picklist-item-cb'));
    var countEl = document.getElementById('picklist-selected-count');
    var bulkPickBtn = document.getElementById('picklist-bulk-pick-btn');
    var bulkUnpickBtn = document.getElementById('picklist-bulk-unpick-btn');

    if (!checkboxes.length || !countEl || !bulkPickBtn || !bulkUnpickBtn) {
        return;
    }

    function selectedCheckboxes() {
        return checkboxes.filter(function(cb) { return cb.checked; });
    }

    function selectedIds() {
        return selectedCheckboxes().map(function(cb) { return cb.value; });
    }

    function countByStatus(status) {
        return selectedCheckboxes().filter(function(cb) {
            return cb.getAttribute('data-status') === status;
        }).length;
    }

    function updateRowSelectionState() {
        document.querySelectorAll('.picklist-select-row').forEach(function(row) {
            var cb = row.querySelector('.picklist-item-cb');
            if (cb && cb.checked) {
                row.classList.add('ring-2', 'ring-inset', 'ring-amber-400', 'bg-amber-50/60');
            } else {
                row.classList.remove('ring-2', 'ring-inset', 'ring-amber-400', 'bg-amber-50/60');
            }
        });
    }

    function countRevertableSelected() {
        return selectedCheckboxes().filter(function(cb) {
            var status = cb.getAttribute('data-status');
            return status === 'picked' || status === 'not_available' || status === 'partially_available';
        }).length;
    }

    function updateBulkBar() {
        var selected = selectedCheckboxes().length;
        var pendingCount = countByStatus('pending');
        var revertableCount = countRevertableSelected();

        countEl.textContent = selected + ' selected';
        bulkPickBtn.disabled = pendingCount === 0;
        bulkUnpickBtn.disabled = revertableCount === 0;

        if (selectAll) {
            selectAll.checked = selected > 0 && selected === checkboxes.length;
            selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        }
        updateRowSelectionState();
    }

    function isRowClickIgnored(target) {
        return !!target.closest('.picklist-row-actions')
            || !!target.closest('.js-picklist-expand-image')
            || !!target.closest('a')
            || !!target.closest('button');
    }

    document.querySelectorAll('.picklist-select-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (isRowClickIgnored(e.target)) return;
            var cb = row.querySelector('.picklist-item-cb');
            if (!cb) return;
            cb.checked = !cb.checked;
            updateBulkBar();
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
            updateBulkBar();
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateBulkBar);
    });

    function postBulk(action, itemIds) {
        var fd = new FormData();
        itemIds.forEach(function(id) {
            fd.append('item_ids[]', id);
        });
        return fetch('index.php?page=picklist&action=' + action, {
            method: 'POST',
            body: fd
        }).then(function(r) { return r.json(); });
    }

    var pickOkClass = 'inline-flex min-w-[6rem] items-center justify-center rounded-xl bg-gradient-to-b from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/15 hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition';
    var revertOkClass = 'inline-flex min-w-[6rem] items-center justify-center rounded-xl bg-gradient-to-b from-amber-600 to-amber-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-amber-900/15 hover:from-amber-700 hover:to-amber-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition';

    bulkPickBtn.addEventListener('click', function() {
        var pendingIds = selectedCheckboxes()
            .filter(function(cb) { return cb.getAttribute('data-status') === 'pending'; })
            .map(function(cb) { return cb.value; });
        if (!pendingIds.length) {
            showAlert('Select at least one pending item to mark as picked.', 'warning');
            return;
        }

        var confirmFn = window.openPicklistConfirmModal || function(opts) {
            return Promise.resolve(window.confirm((opts && opts.message) || 'Are you sure?'));
        };

        confirmFn({
            title: 'Mark selected as picked?',
            message: 'Mark ' + pendingIds.length + ' selected item(s) as picked?',
            confirmText: 'Yes, mark picked',
            cancelText: 'Cancel',
            okClass: pickOkClass
        }).then(function(confirmed) {
            if (!confirmed) return;
            bulkPickBtn.disabled = true;
            bulkUnpickBtn.disabled = true;
            postBulk('bulk_pick_items', pendingIds)
                .then(function(data) {
                    if (data.success) {
                        if (data.picklist_completed) {
                            showAlert((data.message || 'Items marked as picked.') + ' Picklist complete!', 'success');
                        } else {
                            showAlert(data.message || 'Items marked as picked.', 'success');
                        }
                        setTimeout(function() { window.location.reload(); }, 600);
                    } else {
                        showAlert(data.message || 'Bulk pick failed.', 'error');
                        updateBulkBar();
                    }
                })
                .catch(function() {
                    showAlert('Network error.', 'error');
                    updateBulkBar();
                });
        });
    });

    bulkUnpickBtn.addEventListener('click', function() {
        var revertableIds = selectedCheckboxes()
            .filter(function(cb) {
                var status = cb.getAttribute('data-status');
                return status === 'picked' || status === 'not_available' || status === 'partially_available';
            })
            .map(function(cb) { return cb.value; });
        if (!revertableIds.length) {
            showAlert('Select at least one item with a revertable status.', 'warning');
            return;
        }

        var confirmFn = window.openPicklistConfirmModal || function(opts) {
            return Promise.resolve(window.confirm((opts && opts.message) || 'Are you sure?'));
        };

        confirmFn({
            title: 'Revert selected items?',
            message: 'Revert ' + revertableIds.length + ' selected item(s) to pending? Picked orders will be set back to Added to Picklist where applicable.',
            confirmText: 'Yes, revert',
            cancelText: 'Cancel',
            iconWrapClass: 'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-600 shadow-sm',
            iconHtml: '<i class="fas fa-undo text-2xl" aria-hidden="true"></i>',
            okClass: revertOkClass
        }).then(function(confirmed) {
            if (!confirmed) return;
            bulkPickBtn.disabled = true;
            bulkUnpickBtn.disabled = true;
            postBulk('bulk_unpick_items', revertableIds)
                .then(function(data) {
                    if (data.success) {
                        showAlert(data.message || 'Items reverted to pending.', 'success');
                        setTimeout(function() { window.location.reload(); }, 600);
                    } else {
                        showAlert(data.message || 'Bulk revert failed.', 'error');
                        updateBulkBar();
                    }
                })
                .catch(function() {
                    showAlert('Network error.', 'error');
                    updateBulkBar();
                });
        });
    });

    updateBulkBar();
})();
</script>
