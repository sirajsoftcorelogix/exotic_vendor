(function () {
    function namesMatch(left, right) {
        return String(left || '').trim().localeCompare(String(right || '').trim(), undefined, { sensitivity: 'accent' }) === 0;
    }

    function isEditNameUnchanged(getEditId, getName, originalName) {
        return parseInt(getEditId() || '0', 10) > 0
            && originalName !== ''
            && namesMatch(getName(), originalName);
    }

    window.CreatorFormUtils = {
        namesMatch: namesMatch,
        isEditNameUnchanged: isEditNameUnchanged,
        bindNameDuplicateCheck: function (opts) {
            const inputEl = opts.inputEl;
            const msgEl = opts.msgEl;
            if (!inputEl || !msgEl) {
                return;
            }
            inputEl.addEventListener('keyup', function () {
                const value = inputEl.value.trim();
                if (value.length < 2) {
                    opts.setExists(false);
                    msgEl.textContent = '';
                    return;
                }
                let url = 'index.php?page=' + opts.page + '&action=checkName&name=' + encodeURIComponent(value);
                const excludeId = opts.getExcludeId ? parseInt(opts.getExcludeId() || '0', 10) : 0;
                if (excludeId > 0) {
                    url += '&excludeId=' + encodeURIComponent(String(excludeId));
                }
                fetch(url, { credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (opts.isUnchanged && opts.isUnchanged()) {
                            msgEl.textContent = '';
                            opts.setExists(false);
                            return;
                        }
                        if (data.exists) {
                            msgEl.textContent = opts.duplicateMessage;
                            opts.setExists(true);
                        } else {
                            msgEl.textContent = '';
                            opts.setExists(false);
                        }
                    })
                    .catch(function (err) { console.error('Duplicate check error:', err); });
            });
        }
    };
})();
