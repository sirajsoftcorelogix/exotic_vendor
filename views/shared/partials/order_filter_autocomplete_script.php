<?php require_once __DIR__ . '/../../../helpers/order_filter_autocomplete.php'; ?>
document.addEventListener('DOMContentLoaded', function() {
    const MIN_QUERY_LENGTH = <?php echo (int) orderFilterAutocompleteMinLength(); ?>;
    const inputs = document.querySelectorAll('[data-order-filter-autocomplete]');

    inputs.forEach(function(input) {
        const suggestionsBox = document.getElementById(input.dataset.suggestionsTarget || '');
        const searchUrl = input.dataset.searchUrl || '';
        const hiddenInput = input.dataset.hiddenTarget
            ? document.getElementById(input.dataset.hiddenTarget)
            : null;

        if (!suggestionsBox || !searchUrl) {
            return;
        }

        let debounceTimer = null;

        function hideSuggestions() {
            suggestionsBox.style.display = 'none';
        }

        function renderSuggestions(items) {
            suggestionsBox.innerHTML = '';

            if (!Array.isArray(items) || items.length === 0) {
                hideSuggestions();
                return;
            }

            items.forEach(function(item) {
                const name = String(item.name || '').trim();
                if (name === '') {
                    return;
                }

                const option = document.createElement('div');
                option.className = 'p-2 hover:bg-gray-100 cursor-pointer text-xs';
                option.textContent = name;
                option.addEventListener('mousedown', function(event) {
                    event.preventDefault();
                    input.value = name;
                    if (hiddenInput) {
                        hiddenInput.value = item.id != null ? String(item.id) : '';
                    }
                    hideSuggestions();
                });
                suggestionsBox.appendChild(option);
            });

            suggestionsBox.style.display = 'block';
        }

        input.addEventListener('input', function() {
            const query = input.value.trim();

            if (hiddenInput && query === '') {
                hiddenInput.value = '';
            }

            if (query.length < MIN_QUERY_LENGTH) {
                suggestionsBox.innerHTML = '';
                hideSuggestions();
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetch(searchUrl + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function(response) { return response.json(); })
                    .then(function(payload) {
                        const items = Array.isArray(payload)
                            ? payload
                            : (Array.isArray(payload.data) ? payload.data : []);
                        renderSuggestions(items);
                    })
                    .catch(function() {
                        suggestionsBox.innerHTML = '';
                        hideSuggestions();
                    });
            }, 250);
        });

        input.addEventListener('focus', function() {
            if (suggestionsBox.children.length > 0) {
                suggestionsBox.style.display = 'block';
            }
        });

        document.addEventListener('click', function(event) {
            if (!suggestionsBox.contains(event.target) && event.target !== input) {
                hideSuggestions();
            }
        });
    });
});
