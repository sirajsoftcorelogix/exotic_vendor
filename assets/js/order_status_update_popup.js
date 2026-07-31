(function () {
    if (window.__orderStatusPopupInit) {
        return;
    }
    window.__orderStatusPopupInit = true;

    const config = window.OrderStatusPopupConfig || {};
    const updateStatusUrl = config.updateStatusUrl || 'index.php?page=orders&action=update_status';
    const retryStatusApiUrl = config.retryStatusApiUrl || '';
    let exoticApiSyncRetryQueue = [];

    function showInlineError(message) {
        const errorDiv = document.getElementById('orderStatusError');
        if (!errorDiv) {
            return;
        }
        errorDiv.classList.remove('text-green-500', 'text-amber-600');
        errorDiv.classList.add('text-red-500');
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    function closeExoticApiSyncModal() {
        const modal = document.getElementById('exoticApiSyncModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function showExoticApiSyncFailure(apiSync, options) {
        options = options || {};
        const modal = document.getElementById('exoticApiSyncModal');
        if (!modal) {
            return;
        }

        const failures = Array.isArray(apiSync && apiSync.failures) ? apiSync.failures
            : (apiSync && apiSync.attempted && !apiSync.success ? [apiSync] : []);

        if (!failures.length) {
            return;
        }

        exoticApiSyncRetryQueue = failures.map(function (item) {
            return {
                order_id: item.order_id,
                orderStatus: options.orderStatus || document.getElementById('orderStatus')?.value || ''
            };
        });

        const summary = failures.length === 1
            ? ('Order ' + (failures[0].order_number || failures[0].order_id) + ' / ' + (failures[0].item_code || 'item'))
            : (failures.length + ' items failed to sync');

        const details = failures.map(function (item) {
            const parts = [
                'Order: ' + (item.order_number || item.order_id || '—'),
                'Item: ' + (item.item_code || '—'),
                'HTTP: ' + (item.http_code || '—'),
                'Reason: ' + (item.message || 'Unknown error')
            ];
            if (item.raw) {
                parts.push('Response: ' + String(item.raw).slice(0, 400));
            }
            return parts.join('\n');
        }).join('\n\n');

        const summaryEl = document.getElementById('exoticApiSyncSummary');
        const detailsEl = document.getElementById('exoticApiSyncDetails');
        const retryBtn = document.getElementById('exoticApiSyncRetryBtn');
        if (summaryEl) {
            summaryEl.textContent = summary;
        }
        if (detailsEl) {
            detailsEl.textContent = details;
        }
        if (retryBtn) {
            retryBtn.classList.toggle('hidden', exoticApiSyncRetryQueue.length === 0 || !retryStatusApiUrl);
        }
        modal.classList.remove('hidden');
    }

    function retryExoticApiSync() {
        if (!retryStatusApiUrl || !exoticApiSyncRetryQueue.length) {
            closeExoticApiSyncModal();
            return;
        }

        const btn = document.getElementById('exoticApiSyncRetryBtn');
        const originalText = btn ? btn.textContent : '';
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Retrying…';
        }

        const runNext = function (index) {
            if (index >= exoticApiSyncRetryQueue.length) {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
                closeExoticApiSyncModal();
                setTimeout(function () {
                    window.location.reload();
                }, 800);
                return;
            }

            const item = exoticApiSyncRetryQueue[index];
            const formData = new FormData();
            formData.append('order_id', item.order_id);
            if (item.orderStatus) {
                formData.append('orderStatus', item.orderStatus);
            }

            fetch(retryStatusApiUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Retry failed');
                    }
                    runNext(index + 1);
                })
                .catch(function (err) {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                    const detailsEl = document.getElementById('exoticApiSyncDetails');
                    if (detailsEl) {
                        detailsEl.textContent = err.message || 'Retry failed';
                    }
                });
        };

        runNext(0);
    }

    function handleStatusUpdateResponse(data, errorDiv, onComplete) {
        if (!data || !data.success) {
            showInlineError((data && data.message) || 'Error updating order status.');
            return;
        }

        const apiSync = data.api_sync || null;
        const apiFailed = apiSync && apiSync.attempted && !apiSync.success;

        if (apiFailed) {
            errorDiv.classList.remove('text-red-500', 'text-green-500');
            errorDiv.classList.add('text-amber-600');
            errorDiv.textContent = data.message || 'Saved locally, but Exotic India sync failed.';
            errorDiv.classList.remove('hidden');
            showExoticApiSyncFailure(apiSync, {
                orderStatus: document.getElementById('orderStatus')?.value || ''
            });
            return;
        }

        errorDiv.classList.remove('text-red-500', 'text-amber-600');
        errorDiv.classList.add('text-green-500');
        errorDiv.textContent = data.message || 'Order status updated successfully.';
        errorDiv.classList.remove('hidden');

        if (typeof onComplete === 'function') {
            onComplete();
            return;
        }

        setTimeout(function () {
            window.closeStatusPopup();
            window.location.reload();
        }, 1200);
    }

    window.openStatusPopup = function (orderId) {
        const popup = document.getElementById('statusPopup');
        const orderEl = document.getElementById('order-id-' + orderId);
        if (!popup || !orderEl) {
            if (typeof window.showAlert === 'function') {
                window.showAlert('Order data not found.', 'error');
            } else {
                console.error('Order data not found for order-id-' + orderId);
            }
            return;
        }

        let orderData;
        try {
            orderData = JSON.parse(orderEl.getAttribute('data-order') || '{}');
        } catch (err) {
            if (typeof window.showAlert === 'function') {
                window.showAlert('Order data is invalid.', 'error');
            } else {
                console.error('Invalid order data for order-id-' + orderId, err);
            }
            return;
        }

        const menu = document.getElementById('menu-' + orderId);
        if (menu) {
            menu.style.display = 'none';
        }

        document.getElementById('status_order_id').value = orderId;
        document.getElementById('orderRemarks').value = orderData.remarks || '';
        document.getElementById('orderStatus').value = orderData.status || '';
        document.getElementById('status_order_number').textContent = orderData.order_number || 'N/A';
        document.getElementById('status_item_code').textContent = orderData.item_code || 'N/A';

        const vendorEl = document.getElementById('status_vendor_name');
        if (vendorEl) {
            vendorEl.textContent = orderData.vendor_name || orderData.vendor || 'N/A';
        }

        document.getElementById('status_category').textContent = orderData.groupname || 'N/A';
        document.getElementById('status_sub_category').textContent = orderData.subcategories || 'N/A';
        document.getElementById('status_item').textContent = orderData.title || 'N/A';
        document.getElementById('orderPriority').value = orderData.priority || '';
        document.getElementById('previousStatus').value = orderData.status || '';
        document.getElementById('previousAgent').value = orderData.agent_id || '';
        document.getElementById('agentId').value = orderData.agent_id || '';
        document.getElementById('previousPriority').value = orderData.priority || '';
        document.getElementById('previousRemarks').value = orderData.remarks || '';
        document.getElementById('previousESD').value = orderData.esd || '';

        const statusESD = document.getElementById('statusESD');
        const raw = orderData.esd || '';
        if (statusESD) {
            const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
            statusESD.value = m ? m[0] : (raw || '');
        }

        const imgElem = document.querySelector('#statusPopup img');
        if (imgElem) {
            imgElem.src = orderData.image || 'https://placehold.co/100x80/e2e8f0/4a5568?text=Item';
        }

        const errorDiv = document.getElementById('orderStatusError');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.add('hidden');
            errorDiv.classList.remove('text-green-500', 'text-amber-600', 'text-red-500');
        }

        popup.classList.remove('hidden');

        const statusSelect = document.getElementById('orderStatus');
        const fromStatus = String(orderData.status || '').trim();
        if (statusSelect && typeof window.applyOrderWorkflowStatusFilter === 'function') {
            statusSelect.disabled = true;
            window.applyOrderWorkflowStatusFilter(fromStatus, statusSelect)
                .finally(function () {
                    statusSelect.disabled = false;
                });
        }
    };

    window.closeStatusPopup = function (e) {
        if (e && e.target && e.currentTarget !== e.target) {
            return;
        }
        const popup = document.getElementById('statusPopup');
        if (popup) {
            popup.classList.add('hidden');
        }
    };

    window.closeExoticApiSyncModal = closeExoticApiSyncModal;
    window.retryExoticApiSync = retryExoticApiSync;

    document.getElementById('agentId')?.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const agentNameEl = document.getElementById('agentName');
        if (agentNameEl) {
            agentNameEl.value = selectedOption ? selectedOption.text : '';
        }
    });

    document.getElementById('statusForm')?.addEventListener('submit', function (e) {
        const statusSelect = document.getElementById('orderStatus');
        const errorDiv = document.getElementById('orderStatusError');
        if (!statusSelect || statusSelect.value === '') {
            e.preventDefault();
            if (errorDiv) {
                errorDiv.textContent = 'Please select a status.';
                errorDiv.classList.remove('hidden');
            }
            return;
        }

        e.preventDefault();
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }

        const formData = new FormData(document.getElementById('statusForm'));
        fetch(updateStatusUrl, {
            method: 'POST',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                handleStatusUpdateResponse(data, errorDiv);
            })
            .catch(function () {
                showInlineError('An error occurred while updating order status.');
            });
    });
})();
