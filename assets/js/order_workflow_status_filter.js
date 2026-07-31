(function () {
    function cacheSelectOptions(selectEl) {
        if (!selectEl || selectEl.dataset.workflowCached === '1') {
            return;
        }
        selectEl.dataset.workflowCached = '1';
        Array.from(selectEl.options).forEach(function (opt) {
            opt.dataset.workflowOrigDisabled = opt.disabled ? '1' : '0';
        });
    }

    function resetSelectOptions(selectEl) {
        if (!selectEl) {
            return;
        }
        Array.from(selectEl.options).forEach(function (opt) {
            opt.disabled = opt.dataset.workflowOrigDisabled === '1';
        });
    }

    window.applyOrderWorkflowStatusFilter = function (fromSlug, selectEl) {
        if (!selectEl) {
            return Promise.resolve();
        }

        cacheSelectOptions(selectEl);
        resetSelectOptions(selectEl);

        fromSlug = (fromSlug || '').trim();
        if (fromSlug === '') {
            return Promise.resolve();
        }

        var cfg = window.OrderWorkflowStatusFilterConfig || {};
        var baseUrl = String(cfg.allowedTargetsUrl || 'index.php?page=workflow_transition&action=allowedTargets');
        var sep = baseUrl.indexOf('?') >= 0 ? '&' : '?';
        var url = baseUrl + sep + 'from_slug=' + encodeURIComponent(fromSlug);

        return fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Workflow lookup failed');
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success || !data.filter_options) {
                    return;
                }

                var allowed = data.allowed_slugs || [];
                var allowedSet = {};
                allowed.forEach(function (slug) {
                    allowedSet[String(slug).toLowerCase()] = true;
                });

                var currentValue = String(selectEl.value || '').toLowerCase();
                Array.from(selectEl.options).forEach(function (opt) {
                    var value = String(opt.value || '').toLowerCase();
                    if (value === '' || value === currentValue) {
                        return;
                    }
                    if (!allowedSet[value]) {
                        opt.disabled = true;
                    }
                });
            })
            .catch(function () {
                /* keep all options enabled if lookup fails */
            });
    };
})();
