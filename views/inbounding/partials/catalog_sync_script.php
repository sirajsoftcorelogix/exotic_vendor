<script>
(function () {
    function notify(icon, title, body, asHtml) {
        if (typeof Swal === 'undefined') {
            alert(title + (body ? ': ' + String(body).replace(/<[^>]+>/g, '') : ''));
            return;
        }
        var opts = {
            icon: icon,
            title: title,
            showConfirmButton: true,
            confirmButtonText: 'OK',
            confirmButtonColor: '#d97824',
            allowOutsideClick: true,
        };
        opts[asHtml ? 'html' : 'text'] = body || '';
        Swal.fire(opts);
    }

    function showSyncProgress(cfg) {
        if (typeof Swal === 'undefined') {
            return false;
        }
        var html = cfg.progressHtml || '<p class="text-sm text-gray-600" style="margin:0.5rem 0 0;">Syncing from catalog…</p>';
        Swal.fire({
            title: cfg.progressTitle || 'Refreshing…',
            html: html,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            showCancelButton: false,
            didOpen: function () {
                Swal.showLoading();
            },
        });
        return true;
    }

    function showSyncResult(icon, title, body, asHtml) {
        if (typeof Swal === 'undefined') {
            alert(title + (body ? ': ' + String(body).replace(/<[^>]+>/g, '') : ''));
            return;
        }
        Swal.hideLoading();
        var opts = {
            icon: icon,
            title: title,
            showConfirmButton: true,
            confirmButtonText: 'OK',
            confirmButtonColor: '#d97824',
            allowOutsideClick: true,
            allowEscapeKey: true,
        };
        opts[asHtml ? 'html' : 'text'] = body || '';
        Swal.update(opts);
    }

    function bindCatalogSync(btnId, cfg) {
        var btn = document.getElementById(btnId);
        if (!btn || !cfg || !cfg.url) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var run = function () {
                btn.disabled = true;

                var usedProgressModal = showSyncProgress(cfg);

                var method = String(cfg.method || 'GET').toUpperCase();
                var fetchOpts = {
                    method: method,
                    credentials: 'include',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                };
                if (method === 'POST') {
                    fetchOpts.body = cfg.body || new FormData();
                }

                fetch(cfg.url, fetchOpts)
                    .then(function (res) {
                        return res.json().then(function (data) {
                            return { ok: res.ok, data: data };
                        }).catch(function () {
                            return { ok: false, data: {} };
                        });
                    })
                    .then(function (payload) {
                        var data = payload.data || {};
                        if (!cfg.isOk(payload, data)) {
                            var errMsg = typeof cfg.message === 'function' ? cfg.message(false, data) : '';
                            if (usedProgressModal) {
                                showSyncResult('error', cfg.failTitle || 'Refresh failed', errMsg, !!cfg.htmlMessage);
                            } else {
                                notify('error', cfg.failTitle || 'Refresh failed', errMsg, !!cfg.htmlMessage);
                            }
                            return;
                        }
                        var after = cfg.afterSync ? cfg.afterSync(data) : null;
                        return Promise.resolve(after).then(
                            function () {
                                var okMsg = typeof cfg.message === 'function' ? cfg.message(true, data) : '';
                                if (usedProgressModal) {
                                    showSyncResult('success', cfg.successTitle || 'Refreshed', okMsg, !!cfg.htmlMessage);
                                } else {
                                    notify('success', cfg.successTitle || 'Refreshed', okMsg, !!cfg.htmlMessage);
                                }
                            },
                            function () {
                                var warnMsg = cfg.warnMessage || 'Saved, but the form could not reload.';
                                if (usedProgressModal) {
                                    showSyncResult('warning', cfg.warnTitle || cfg.successTitle || 'Refreshed', warnMsg, !!cfg.htmlMessage);
                                } else {
                                    notify('warning', cfg.warnTitle || cfg.successTitle || 'Refreshed', warnMsg, !!cfg.htmlMessage);
                                }
                            }
                        );
                    })
                    .catch(function (err) {
                        var netMsg = (err && err.message) || 'Network error.';
                        if (usedProgressModal) {
                            showSyncResult('error', cfg.failTitle || 'Refresh failed', netMsg, false);
                        } else {
                            notify('error', cfg.failTitle || 'Refresh failed', netMsg, false);
                        }
                    })
                    .finally(function () {
                        btn.disabled = false;
                    });
            };

            if (typeof Swal === 'undefined') {
                if (window.confirm(cfg.confirmTitle || 'Refresh from catalog?')) run();
                return;
            }
            Swal.fire({
                title: cfg.confirmTitle || 'Refresh?',
                html: cfg.confirmHtml || '',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Refresh now',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d97824',
            }).then(function (result) {
                if (result.isConfirmed) run();
            });
        });
    }

    window.bindInboundCatalogSync = bindCatalogSync;
    window.bindDesktopCacheSync = bindCatalogSync;
})();
</script>
