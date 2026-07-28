<script>
(function() {
    function closeAllRowMenus(except) {
        document.querySelectorAll('.picklist-row-menu-panel').forEach(function(panel) {
            if (panel !== except) {
                panel.classList.add('hidden');
                var btn = panel.parentElement && panel.parentElement.querySelector('.picklist-row-menu-btn');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }

    document.querySelectorAll('.picklist-row-menu-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var panel = btn.parentElement && btn.parentElement.querySelector('.picklist-row-menu-panel');
            if (!panel) return;
            var willOpen = panel.classList.contains('hidden');
            closeAllRowMenus(willOpen ? panel : null);
            panel.classList.toggle('hidden', !willOpen);
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.picklist-row-menu')) return;
        closeAllRowMenus(null);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllRowMenus(null);
        }
    });
})();
</script>
