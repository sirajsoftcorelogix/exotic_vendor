<style>
    .search-hidden { display: none !important; }
</style>
<script>
(function() {
    var searchInput = document.getElementById('picklist-item-search');
    var clearBtn = document.getElementById('picklist-search-clear');
    if (!searchInput) return;

    function performSearch() {
        var query = searchInput.value.trim().toLowerCase();
        if (clearBtn) {
            if (query.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }

        var terms = query.length > 0 ? query.split(/\s+/) : [];

        var rows = document.querySelectorAll('.picklist-item-row, .pick-item');
        rows.forEach(function(row) {
            var searchText = row.getAttribute('data-search-text') || '';
            if (!searchText) {
                searchText = (row.textContent || '').toLowerCase();
            }

            var matches = true;
            if (terms.length > 0) {
                matches = terms.every(function(term) {
                    return searchText.indexOf(term) !== -1;
                });
            }

            if (matches) {
                row.classList.remove('search-hidden');
            } else {
                row.classList.add('search-hidden');
            }
        });

        document.querySelectorAll('.js-picklist-tab-panel, #tablet-items').forEach(function(container) {
            var allRows = container.querySelectorAll('.picklist-item-row, .pick-item');
            if (!allRows.length) return;
            var hiddenRows = container.querySelectorAll('.picklist-item-row.search-hidden, .pick-item.search-hidden');
            var noMatchMsg = container.querySelector('.js-picklist-no-search-match');

            if (hiddenRows.length === allRows.length && allRows.length > 0) {
                if (!noMatchMsg) {
                    var tbody = container.querySelector('tbody');
                    if (tbody) {
                        var tr = document.createElement('tr');
                        tr.className = 'js-picklist-no-search-match';
                        tr.innerHTML = '<td colspan="12" class="px-3 py-12 text-center text-gray-500 font-medium text-sm"><i class="fas fa-search mr-2 text-gray-400" aria-hidden="true"></i>No items match your search.</td>';
                        tbody.appendChild(tr);
                    } else {
                        var div = document.createElement('div');
                        div.className = 'js-picklist-no-search-match rounded-2xl border border-gray-200 bg-white p-8 text-center text-gray-500 font-medium text-sm';
                        div.innerHTML = '<i class="fas fa-search text-lg mr-2 text-gray-400" aria-hidden="true"></i>No items match your search.';
                        container.appendChild(div);
                    }
                } else {
                    noMatchMsg.classList.remove('hidden');
                }
            } else if (noMatchMsg) {
                noMatchMsg.classList.add('hidden');
            }
        });

        if (typeof window.updatePicklistBulkBar === 'function') {
            window.updatePicklistBulkBar();
        }
    }

    searchInput.addEventListener('input', performSearch);

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        });
    }
})();
</script>
