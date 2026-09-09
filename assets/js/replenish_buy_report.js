document.addEventListener('DOMContentLoaded', function () {
    const notice = function (message, tone) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, tone || 'info');
            return;
        }
        if (typeof window.showPosMessageModal === 'function') {
            window.showPosMessageModal({ title: 'Replenishment', message: message, tone: tone || 'info' });
        }
    };

    const rowChecks = document.querySelectorAll('.replenish-row-check');
    const selectAll = document.getElementById('replenishSelectAll');
    const selectBar = document.getElementById('replenishSelectBar');
    const selectSummary = document.getElementById('replenishSelectSummary');
    const selectSameBtn = document.getElementById('replenishSelectSameVendor');
    const selectClearBtn = document.getElementById('replenishSelectClear');
    const sameVendorOnlyMessage = 'You can only select items from one vendor. Clear the current selection first, then choose another vendor.';
    const storageKey = 'replenishBuySelection';

    const rowVendorKey = function (cb) {
        var key = (cb.getAttribute('data-vendor-key') || '').trim();
        if (key !== '') {
            return key;
        }
        return 'row:' + (cb.getAttribute('data-id') || '');
    };

    const emptySelection = function () {
        return { vendorKey: '', vendorId: '', vendorName: '', items: {} };
    };

    const readSelection = function () {
        try {
            var raw = sessionStorage.getItem(storageKey);
            if (!raw) {
                return emptySelection();
            }
            var data = JSON.parse(raw);
            if (!data || typeof data.items !== 'object' || data.items === null) {
                return emptySelection();
            }
            return data;
        } catch (e) {
            return emptySelection();
        }
    };

    const writeSelection = function (state) {
        if (!state || !state.items || Object.keys(state.items).length === 0) {
            sessionStorage.removeItem(storageKey);
            return;
        }
        sessionStorage.setItem(storageKey, JSON.stringify(state));
    };

    const selectionCount = function (state) {
        return Object.keys((state && state.items) || {}).length;
    };

    const itemFromCheckbox = function (cb) {
        return {
            id: parseInt(cb.getAttribute('data-id') || '0', 10),
            productId: parseInt(cb.getAttribute('data-product-id') || '0', 10),
            buyQty: parseInt(cb.getAttribute('data-buy-qty') || '0', 10),
            vendorId: cb.getAttribute('data-vendor-id') || '',
            vendorName: (cb.getAttribute('data-vendor-name') || '').trim(),
            vendorKey: rowVendorKey(cb)
        };
    };

    const addSelectionItems = function (list) {
        var state = readSelection();
        var added = 0;
        list.forEach(function (item) {
            if (!item || !item.id) {
                return;
            }
            var key = (item.vendorKey || '').trim();
            if (key === '') {
                return;
            }
            if (state.vendorKey && state.vendorKey !== key) {
                return;
            }
            if (!state.vendorKey) {
                state.vendorKey = key;
                state.vendorId = item.vendorId || '';
                state.vendorName = item.vendorName || '';
            }
            var idKey = String(item.id);
            if (!state.items[idKey]) {
                added++;
            }
            state.items[idKey] = item;
        });
        writeSelection(state);
        return added;
    };

    const removeSelectionIds = function (ids) {
        var state = readSelection();
        ids.forEach(function (id) {
            delete state.items[String(id)];
        });
        if (selectionCount(state) === 0) {
            writeSelection(emptySelection());
            return;
        }
        writeSelection(state);
    };

    const applySelectionToPage = function () {
        var state = readSelection();
        rowChecks.forEach(function (cb) {
            var id = cb.getAttribute('data-id') || '';
            cb.checked = !!(state.items && state.items[id]);
        });
    };

    const updateSelectUi = function () {
        var state = readSelection();
        var locked = state.vendorKey || '';
        var count = selectionCount(state);
        var pageSameVendor = [];
        rowChecks.forEach(function (cb) {
            if (locked === '' || rowVendorKey(cb) === locked) {
                pageSameVendor.push(cb);
            }
        });
        var pageSelected = pageSameVendor.filter(function (cb) { return cb.checked; }).length;
        if (selectAll) {
            selectAll.checked = locked !== '' && pageSameVendor.length > 0 && pageSelected === pageSameVendor.length;
            selectAll.indeterminate = pageSelected > 0 && pageSelected < pageSameVendor.length;
        }
        rowChecks.forEach(function (cb) {
            var tr = cb.closest('tr');
            var otherVendor = locked !== '' && rowVendorKey(cb) !== locked;
            cb.disabled = otherVendor;
            if (tr) {
                tr.classList.toggle('bg-amber-50/80', cb.checked);
                tr.classList.toggle('opacity-50', otherVendor);
            }
        });
        if (!selectBar || !selectSummary) {
            return;
        }
        if (count === 0) {
            selectBar.classList.add('hidden');
            selectBar.classList.remove('flex');
            return;
        }
        selectBar.classList.remove('hidden');
        selectBar.classList.add('flex');
        var label = count + (count === 1 ? ' item selected' : ' items selected');
        if (state.vendorName) {
            label += ' · ' + state.vendorName;
        }
        label += ' (kept across pages)';
        selectSummary.textContent = label;
    };

    const selectVisibleByVendorKey = function (key) {
        if (key === '') {
            return [];
        }
        var items = [];
        rowChecks.forEach(function (cb) {
            if (rowVendorKey(cb) === key) {
                cb.checked = true;
                items.push(itemFromCheckbox(cb));
            }
        });
        return items;
    };

    const fetchSameVendorAllPages = function (item, done) {
        var url = (selectBar && selectBar.getAttribute('data-same-vendor-url')) || '';
        if (url === '') {
            addSelectionItems(selectVisibleByVendorKey(item.vendorKey));
            applySelectionToPage();
            updateSelectUi();
            if (done) {
                done(selectionCount(readSelection()));
            }
            return;
        }
        var query = url.indexOf('?') >= 0 ? url.split('?')[1] : '';
        var params = new URLSearchParams(query);
        params.set('lock_vendor_id', item.vendorId || '0');
        params.set('lock_vendor_name', item.vendorName || '');
        params.set('lock_vendor_key', item.vendorKey || '');
        fetch('index.php?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success || !Array.isArray(data.rows)) {
                    notice(data.message || 'Could not load vendor items.', 'error');
                    return;
                }
                addSelectionItems(data.rows);
                applySelectionToPage();
                updateSelectUi();
                if (done) {
                    done(selectionCount(readSelection()));
                }
            })
            .catch(function () {
                notice('Could not load vendor items from other pages.', 'error');
            });
    };

    applySelectionToPage();
    updateSelectUi();

    rowChecks.forEach(function (cb) {
        cb.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        cb.addEventListener('change', function () {
            var key = rowVendorKey(cb);
            var state = readSelection();
            var locked = state.vendorKey || '';
            var rowId = cb.getAttribute('data-id') || '';
            if (cb.checked) {
                if (locked !== '' && locked !== key) {
                    cb.checked = false;
                    notice(sameVendorOnlyMessage, 'warning');
                    updateSelectUi();
                    return;
                }
                var addedItems = selectVisibleByVendorKey(key);
                addSelectionItems(addedItems);
                applySelectionToPage();
                var total = selectionCount(readSelection());
                var vendorName = (cb.getAttribute('data-vendor-name') || '').trim();
                if (addedItems.length > 1 || total > 1) {
                    notice('Selected ' + total + ' items' + (vendorName !== '' ? ' from ' + vendorName : ' with the same vendor') + '. Selection is kept when you change pages.', 'info');
                }
            } else {
                removeSelectionIds([rowId]);
            }
            updateSelectUi();
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var state = readSelection();
            var locked = state.vendorKey || '';
            if (!selectAll.checked) {
                var ids = [];
                rowChecks.forEach(function (cb) {
                    if (cb.checked) {
                        ids.push(cb.getAttribute('data-id') || '');
                        cb.checked = false;
                    }
                });
                removeSelectionIds(ids);
                updateSelectUi();
                return;
            }
            if (locked === '') {
                var firstKey = rowChecks.length ? rowVendorKey(rowChecks[0]) : '';
                var mixed = Array.prototype.some.call(rowChecks, function (cb) {
                    return rowVendorKey(cb) !== firstKey;
                });
                if (mixed) {
                    selectAll.checked = false;
                    notice('Select one item first. You can only select items from a single vendor.', 'warning');
                    updateSelectUi();
                    return;
                }
                locked = firstKey;
            }
            addSelectionItems(selectVisibleByVendorKey(locked));
            applySelectionToPage();
            updateSelectUi();
        });
    }

    if (selectSameBtn) {
        selectSameBtn.addEventListener('click', function () {
            var state = readSelection();
            if (!state.vendorKey) {
                notice('Select at least one row that has a vendor.', 'warning');
                return;
            }
            fetchSameVendorAllPages({
                vendorKey: state.vendorKey,
                vendorId: state.vendorId,
                vendorName: state.vendorName
            }, function (total) {
                notice('Selected ' + total + ' items from this vendor across all pages.', 'info');
            });
        });
    }

    if (selectClearBtn) {
        selectClearBtn.addEventListener('click', function () {
            writeSelection(emptySelection());
            applySelectionToPage();
            updateSelectUi();
        });
    }

    var createPoBtn = document.getElementById('replenishCreatePoBtn');
    var createPoForm = document.getElementById('replenishCreatePoForm');
    if (createPoBtn && createPoForm) {
        createPoBtn.addEventListener('click', function () {
            var state = readSelection();
            var items = Object.keys(state.items || {}).map(function (id) { return state.items[id]; });
            if (!items.length) {
                notice('Select at least one item to create a purchase order.', 'warning');
                return;
            }
            var missingProduct = items.some(function (item) {
                return parseInt(item.productId || '0', 10) <= 0;
            });
            if (missingProduct) {
                notice('One or more selected rows are missing a product. Refresh and try again.', 'error');
                return;
            }
            createPoForm.innerHTML = '';
            var addHidden = function (name, value) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                createPoForm.appendChild(input);
            };
            if (state.vendorId && String(state.vendorId) !== '0') {
                addHidden('vendor_id', state.vendorId);
            }
            if (state.vendorName) {
                addHidden('vendor_name', state.vendorName);
            }
            items.forEach(function (item) {
                addHidden('cpoitem[]', String(item.productId));
                addHidden('cpoqty[' + item.productId + ']', String(item.buyQty || 0));
            });
            createPoForm.submit();
        });
    }

    document.querySelectorAll('.replenish-purchased-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = parseInt(btn.getAttribute('data-id') || '0', 10);
            const currentlyYes = btn.getAttribute('data-purchased') === '1';
            fetch('index.php?page=replenishment_buy_report&action=mark_purchased', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, purchased: currentlyYes ? 0 : 1 })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    notice(data.message || 'Could not update.', 'error');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                notice('Could not update purchased status.', 'error');
            });
        });
    });

    const runBtn = document.getElementById('runReplenishmentJobBtn');
    if (runBtn) {
        runBtn.disabled = false;
        runBtn.removeAttribute('disabled');
        runBtn.addEventListener('click', function () {
            runBtn.disabled = true;
            fetch('index.php?page=replenishment_buy_report&action=run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (res) { return res.text(); })
            .then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error(text ? text.replace(/<[^>]+>/g, ' ').slice(0, 180) : 'Invalid server response');
                }
                notice(data.message || 'Job finished.', data.success ? 'success' : 'error');
                if (!data.success) {
                    runBtn.disabled = false;
                    return;
                }
                var params = new URLSearchParams(window.location.search);
                params.set('page', 'replenishment_buy_report');
                params.set('action', 'list');
                params.set('purchased', 'no');
                if (data.summary && data.summary.run_date) {
                    params.set('date_from', data.summary.run_date);
                    params.set('date_to', data.summary.run_date);
                    params.delete('run_date');
                }
                var nextSearch = '?' + params.toString();
                if (window.location.search === nextSearch) {
                    window.location.reload();
                    return;
                }
                window.location.search = nextSearch;
                window.setTimeout(function () {
                    runBtn.disabled = false;
                }, 1500);
            })
            .catch(function (err) {
                runBtn.disabled = false;
                notice((err && err.message) ? err.message : 'Could not run replenishment job.', 'error');
            });
        });
    }
});
