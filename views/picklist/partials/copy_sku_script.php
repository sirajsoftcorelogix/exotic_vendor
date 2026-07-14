<script>
(function () {
    function copyLabel(btn) {
        return btn.getAttribute('data-copy-label') || 'Text';
    }

    function copyTitle(btn) {
        var label = copyLabel(btn);
        return 'Copy ' + label.toLowerCase();
    }

    function copyAriaLabel(btn) {
        var label = copyLabel(btn);
        var text = btn.getAttribute('data-copy-text') || '';
        return text ? 'Copy ' + label.toLowerCase() + ' ' + text : 'Copy ' + label.toLowerCase();
    }

    function flashCopied(btn) {
        if (!btn) return;
        var icon = btn.querySelector('i');
        if (!icon) return;
        var prev = icon.className;
        icon.className = 'fas fa-check text-[10px] text-emerald-600';
        btn.setAttribute('title', 'Copied');
        btn.setAttribute('aria-label', 'Copied');
        setTimeout(function () {
            icon.className = prev;
            btn.setAttribute('title', copyTitle(btn));
            btn.setAttribute('aria-label', copyAriaLabel(btn));
        }, 1200);
    }

    function copyText(text, btn) {
        text = String(text || '').trim();
        if (!text) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                flashCopied(btn);
            }).catch(function () {
                legacyCopy(text, btn);
            });
            return;
        }
        legacyCopy(text, btn);
    }

    function legacyCopy(text, btn) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            if (document.execCommand('copy')) {
                flashCopied(btn);
            }
        } finally {
            document.body.removeChild(ta);
        }
    }

    document.querySelectorAll('.js-picklist-copy-text').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            copyText(btn.getAttribute('data-copy-text'), btn);
        });
    });
})();
</script>
