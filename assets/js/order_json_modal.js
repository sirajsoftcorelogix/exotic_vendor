(function () {
    'use strict';

    var cfg = window.orderJsonModalConfig || {};
    var lastPayload = null;

    function el(id) {
        return document.getElementById(id);
    }

    function closeModal(event) {
        if (event && event.target && event.currentTarget !== event.target) {
            return;
        }
        var modal = el('orderJsonModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function setLoading(isLoading) {
        var loadingEl = el('orderJsonLoading');
        if (loadingEl) {
            loadingEl.classList.toggle('hidden', !isLoading);
        }
    }

    function renderResult(data) {
        setLoading(false);

        var errorEl = el('orderJsonError');
        var metaEl = el('orderJsonMeta');
        var preEl = el('orderJsonPre');
        var copyBtn = el('orderJsonCopyBtn');

        if (!data || !data.success) {
            lastPayload = data && data.response ? data.response : null;
            if (errorEl) {
                errorEl.textContent = (data && data.message) ? data.message : 'Could not fetch order JSON.';
                errorEl.classList.remove('hidden');
            }
            if (metaEl) {
                metaEl.classList.add('hidden');
            }
            if (preEl) {
                if (lastPayload) {
                    preEl.textContent = JSON.stringify(lastPayload, null, 2);
                    preEl.classList.remove('hidden');
                } else {
                    preEl.classList.add('hidden');
                    preEl.textContent = '';
                }
            }
            if (copyBtn) {
                copyBtn.disabled = !lastPayload;
            }
            return;
        }

        lastPayload = data.response || data.order || null;
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
        }
        if (metaEl) {
            var fetchedAt = data.fetched_at ? ('Fetched at ' + data.fetched_at) : 'Fetched just now';
            metaEl.textContent = fetchedAt + ' — showing full API response envelope.';
            metaEl.classList.remove('hidden');
        }
        if (preEl) {
            preEl.textContent = JSON.stringify(lastPayload, null, 2);
            preEl.classList.remove('hidden');
        }
        if (copyBtn) {
            copyBtn.disabled = !lastPayload;
        }
    }

    function refetch() {
        var orderNumber = String(cfg.orderNumber || '').trim();
        if (!orderNumber) {
            renderResult({ success: false, message: 'Order number is missing.' });
            return Promise.resolve();
        }

        var errorEl = el('orderJsonError');
        var metaEl = el('orderJsonMeta');
        var preEl = el('orderJsonPre');
        var copyBtn = el('orderJsonCopyBtn');

        setLoading(true);
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
        }
        if (metaEl) {
            metaEl.classList.add('hidden');
        }
        if (preEl) {
            preEl.classList.add('hidden');
            preEl.textContent = '';
        }
        if (copyBtn) {
            copyBtn.disabled = true;
        }

        var baseUrl = String(cfg.fetchUrl || 'index.php?page=posorders&action=fetch_order_json');
        var url = baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'order_number=' + encodeURIComponent(orderNumber);

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    var parsed;
                    try {
                        parsed = JSON.parse(text);
                    } catch (err) {
                        throw new Error((text || '').trim().slice(0, 200) || 'Invalid server response');
                    }
                    if (!res.ok && parsed && !parsed.message) {
                        parsed.message = 'Request failed (' + res.status + ')';
                        parsed.success = false;
                    }
                    return parsed;
                });
            })
            .then(renderResult)
            .catch(function (err) {
                renderResult({
                    success: false,
                    message: err.message || 'Could not fetch order JSON.',
                });
            });
    }

    function openModal() {
        var modal = el('orderJsonModal');
        if (!modal) {
            return;
        }
        modal.classList.remove('hidden');
        refetch();
    }

    function copyJson() {
        if (!lastPayload) {
            return;
        }
        var text = JSON.stringify(lastPayload, null, 2);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                alert('JSON copied to clipboard.');
            }).catch(function () {
                alert('Could not copy to clipboard.');
            });
            return;
        }
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            alert('JSON copied to clipboard.');
        } catch (e) {
            alert('Could not copy to clipboard.');
        }
        document.body.removeChild(ta);
    }

    window.openOrderJsonModal = openModal;
    window.closeOrderJsonModal = closeModal;
    window.refetchOrderJson = refetch;
    window.copyOrderJson = copyJson;
})();
