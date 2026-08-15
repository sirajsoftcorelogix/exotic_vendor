<script>
(function() {
    var sortBtns = document.querySelectorAll('.js-picklist-sort-btn');
    if (!sortBtns.length) return;

    var collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

    sortBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var sortKey = btn.getAttribute('data-sort-key');
            if (!sortKey) return;

            var table = btn.closest('table');
            if (!table) return;

            var currentDir = btn.getAttribute('data-direction') || '';
            var nextDir = currentDir === 'asc' ? 'desc' : 'asc';

            // Reset all sort buttons in this table
            table.querySelectorAll('.js-picklist-sort-btn').forEach(function(otherBtn) {
                otherBtn.removeAttribute('data-direction');
                var iconWrap = otherBtn.querySelector('.js-sort-icon');
                if (iconWrap) {
                    iconWrap.innerHTML = '<i class="fas fa-sort text-gray-400" aria-hidden="true"></i>';
                }
            });

            btn.setAttribute('data-direction', nextDir);
            var activeIconWrap = btn.querySelector('.js-sort-icon');
            if (activeIconWrap) {
                if (nextDir === 'asc') {
                    activeIconWrap.innerHTML = '<i class="fas fa-sort-up text-amber-700" aria-hidden="true"></i>';
                } else {
                    activeIconWrap.innerHTML = '<i class="fas fa-sort-down text-amber-700" aria-hidden="true"></i>';
                }
            }

            var tbody = table.querySelector('tbody');
            if (!tbody) return;

            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr.picklist-item-row'));
            var attrName = 'data-sort-' + sortKey;

            rows.sort(function(rowA, rowB) {
                var valA = (rowA.getAttribute(attrName) || '').trim();
                var valB = (rowB.getAttribute(attrName) || '').trim();

                if (!valA && valB) return 1;
                if (valA && !valB) return -1;
                if (!valA && !valB) return 0;

                var cmp = collator.compare(valA, valB);
                return nextDir === 'asc' ? cmp : -cmp;
            });

            rows.forEach(function(row, idx) {
                tbody.appendChild(row);
                var numCell = row.cells[1];
                if (numCell && numCell.classList.contains('tabular-nums')) {
                    numCell.textContent = idx + 1;
                }
            });

            if (typeof window.updatePicklistBulkBar === 'function') {
                window.updatePicklistBulkBar();
            }
        });
    });
})();
</script>
