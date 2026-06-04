<script>
(function () {
    var SPIN = '<svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    function notify(icon, title, body, asHtml) {
        if (typeof Swal === 'undefined') {
            alert(title + (body ? ': ' + String(body).replace(/<[^>]+>/g, '') : ''));
            return;
        }
        var opts = { icon: icon, title: title };
        opts[asHtml ? 'html' : 'text'] = body || '';
        Swal.fire(opts);
    }

    function bindCatalogSync(btnId, cfg) {
        var btn = document.getElementById(btnId);
        if (!btn || !cfg || !cfg.url) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var run = function () {
                var orig = btn.innerHTML;
                btn.disabled = true;
                btn.classList.add('opacity-60', 'cursor-wait');
                btn.innerHTML = SPIN;

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
                            notify('error', cfg.failTitle || 'Refresh failed', typeof cfg.message === 'function' ? cfg.message(false, data) : '', !!cfg.htmlMessage);
                            return;
                        }
                        var after = cfg.afterSync ? cfg.afterSync(data) : null;
                        return Promise.resolve(after).then(
                            function () {
                                notify('success', cfg.successTitle || 'Refreshed', typeof cfg.message === 'function' ? cfg.message(true, data) : '', !!cfg.htmlMessage);
                            },
                            function () {
                                notify('warning', cfg.warnTitle || cfg.successTitle || 'Refreshed', cfg.warnMessage || 'Saved, but the form could not reload.', !!cfg.htmlMessage);
                            }
                        );
                    })
                    .catch(function (err) {
                        notify('error', cfg.failTitle || 'Refresh failed', (err && err.message) || 'Network error.');
                    })
                    .finally(function () {
                        btn.disabled = false;
                        btn.classList.remove('opacity-60', 'cursor-wait');
                        btn.innerHTML = orig;
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
