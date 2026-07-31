<script>
(function () {
    if (window.initIsbnLookup) {
        return;
    }

    window.ISBN_LOOKUP_DEFAULT_URL = <?php echo json_encode(base_url('?page=inbounding&action=search_book_attr'), JSON_UNESCAPED_SLASHES); ?>;

    window.initIsbnLookup = function (deps) {
        deps = deps || {};
        const authorTomSelect = deps.authorTomSelect || null;
        const publisherSelect = deps.publisherSelect || null;
        const syncAuthorPipeValue = deps.syncAuthorPipeValue || function () {};
        const isbnLookupUrl = deps.isbnLookupUrl || window.ISBN_LOOKUP_DEFAULT_URL || '';
        const lookupBtn = document.getElementById('isbn-lookup-btn');
        const isbnInput = document.getElementById('isbn_input');
        const modal = document.getElementById('isbnLookupModal');
        const closeBtn = document.getElementById('isbnLookupCloseBtn');
        const cancelBtn = document.getElementById('isbnLookupCancelBtn');
        const applyBtn = document.getElementById('isbnLookupApplyBtn');
        const messageEl = document.getElementById('isbnLookupMessage');
        const detailsEl = document.getElementById('isbnLookupDetails');
        const warningsEl = document.getElementById('isbnLookupWarnings');
        const coverImg = document.getElementById('isbnLookupCover');
        const coverPlaceholder = document.getElementById('isbnLookupCoverPlaceholder');

        if (!lookupBtn || !isbnInput || !modal) {
            return;
        }

        let pendingLookupPayload = null;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function setLookupButtonLoading(isLoading) {
            lookupBtn.disabled = isLoading;
            lookupBtn.textContent = isLoading ? 'Searching...' : 'Lookup';
            lookupBtn.classList.toggle('opacity-70', isLoading);
            lookupBtn.classList.toggle('cursor-not-allowed', isLoading);
        }

        function closeIsbnLookupModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            pendingLookupPayload = null;
            applyBtn.disabled = false;
        }

        function openIsbnLookupModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        modal.__closeIsbnLookup = closeIsbnLookupModal;

        function renderDetailRow(label, value) {
            const text = String(value ?? '').trim();
            if (!text) {
                return '';
            }
            return '<div><span class="font-bold text-gray-700">' + escapeHtml(label) + ':</span> ' + escapeHtml(text) + '</div>';
        }

        function formatProviderStatusHints(providerStatus) {
            if (!providerStatus || typeof providerStatus !== 'object') {
                return '';
            }

            const hints = [];
            Object.keys(providerStatus).forEach(function (providerKey) {
                const status = providerStatus[providerKey];
                if (!status || !status.state || status.state === 'ok') {
                    return;
                }
                const label = status.label || status.state;
                if (providerKey === 'open_library') {
                    hints.push('Open Library: ' + label);
                } else if (providerKey === 'google_books') {
                    hints.push('Google Books: ' + label);
                } else if (providerKey === 'vp_catalog') {
                    hints.push('Exotic Catalog: ' + label);
                } else {
                    hints.push(label);
                }
            });

            return hints.join('\n');
        }

        function renderLookupPreview(payload) {
            const data = payload.data || {};
            const catalog = payload.catalog_matches || {};

            messageEl.textContent = payload.message || 'Book details found.';

            const authors = Array.isArray(data.authors) ? data.authors.join(', ') : '';
            const subjects = Array.isArray(data.subjects) ? data.subjects.join(', ') : '';
            const sources = Array.isArray(data.sources)
                ? data.sources.map(function (source) {
                    if (source === 'open_library') return 'Open Library';
                    if (source === 'google_books') return 'Google Books';
                    if (source === 'vp_catalog') return 'Exotic Catalog';
                    return source;
                }).join(', ')
                : '';

            detailsEl.innerHTML = [
                renderDetailRow('Title', data.title),
                data.subtitle ? renderDetailRow('Subtitle', data.subtitle) : '',
                renderDetailRow('Authors', authors),
                renderDetailRow('Publisher', data.publisher),
                renderDetailRow('ISBN', data.isbn),
                renderDetailRow('Pages', data.pages),
                renderDetailRow('Cover Type', data.cover_type),
                renderDetailRow('Edition', data.edition),
                renderDetailRow('Publication Date', data.publication_date),
                renderDetailRow('Language', data.language),
                renderDetailRow('Subjects', subjects),
                renderDetailRow('Description', data.description),
                renderDetailRow('Sources', sources),
            ].join('');

            const warnings = [];
            if (Array.isArray(catalog.unmatched_authors) && catalog.unmatched_authors.length) {
                warnings.push('Author(s) not found in catalog — please search manually: ' + catalog.unmatched_authors.join(', '));
            }
            if (catalog.unmatched_publisher) {
                warnings.push('Publisher not found in catalog — please search manually: ' + catalog.unmatched_publisher);
            }
            if (!data.pages && !data.cover_type) {
                warnings.push('Pages and cover type are not listed in the catalog for this ISBN — enter manually if needed.');
            }

            const googleStatus = (payload.provider_status && payload.provider_status.google_books) ? payload.provider_status.google_books : null;
            if (googleStatus && googleStatus.state && googleStatus.state !== 'ok') {
                warnings.push('Google Books: ' + (googleStatus.label || googleStatus.state));
            }

            if (warnings.length) {
                warningsEl.textContent = warnings.join(' ');
                warningsEl.classList.remove('hidden');
            } else {
                warningsEl.textContent = '';
                warningsEl.classList.add('hidden');
            }

            if (data.cover_url) {
                coverPlaceholder.textContent = 'No cover';
                coverImg.src = data.cover_url;
                coverImg.classList.remove('hidden');
                coverPlaceholder.classList.add('hidden');
                coverImg.onerror = function () {
                    coverImg.classList.add('hidden');
                    coverPlaceholder.textContent = 'No cover image in catalog';
                    coverPlaceholder.classList.remove('hidden');
                };
            } else {
                coverImg.classList.add('hidden');
                coverImg.removeAttribute('src');
                coverPlaceholder.classList.remove('hidden');
            }
        }

        function setFormFieldValue(name, value) {
            const field = document.querySelector('[name="' + name + '"]');
            if (!field || value === undefined || value === null) {
                return;
            }
            const text = String(value).trim();
            if (text === '') {
                return;
            }
            field.value = text;
        }

        function applyIsbnLookupToForm(payload) {
            const data = payload.data || {};
            const catalog = payload.catalog_matches || {};

            setFormFieldValue('isbn', data.isbn);
            setFormFieldValue('pages', data.pages);
            setFormFieldValue('edition', data.edition);
            setFormFieldValue('publication_date', data.publication_date);
            setFormFieldValue('language', data.language);

            const coverTypeSelect = document.querySelector('select[name="cover_type"]');
            if (coverTypeSelect && data.cover_type) {
                const wanted = String(data.cover_type).trim();
                let matched = false;
                Array.prototype.forEach.call(coverTypeSelect.options, function (option) {
                    if (option.value === wanted) {
                        coverTypeSelect.value = wanted;
                        matched = true;
                    }
                });
                if (!matched) {
                    const wantedLower = wanted.toLowerCase();
                    Array.prototype.forEach.call(coverTypeSelect.options, function (option) {
                        if (!matched && option.value.toLowerCase() === wantedLower) {
                            coverTypeSelect.value = option.value;
                            matched = true;
                        }
                    });
                }
            }

            if (authorTomSelect && Array.isArray(catalog.authors) && catalog.authors.length) {
                const authorIds = [];
                catalog.authors.forEach(function (author) {
                    const id = String(author.id || '');
                    const name = String(author.name || '');
                    if (!id) {
                        return;
                    }
                    authorTomSelect.addOption({ id: id, name: name });
                    authorIds.push(id);
                });
                if (authorIds.length) {
                    authorTomSelect.setValue(authorIds);
                    syncAuthorPipeValue(authorTomSelect);
                }
            }

            if (publisherSelect && catalog.publisher && catalog.publisher.id) {
                const publisherId = String(catalog.publisher.id);
                const publisherName = String(catalog.publisher.name || '');
                publisherSelect.addOption({ id: publisherId, name: publisherName });
                publisherSelect.setValue(publisherId);
            }

            if (typeof deps.onApply === 'function') {
                deps.onApply(payload);
            }
        }

        function runIsbnLookup() {
            const isbn = String(isbnInput.value || '').trim();
            if (!isbn) {
                alert('Please enter an ISBN before lookup.');
                isbnInput.focus();
                return;
            }

            setLookupButtonLoading(true);
            fetch(isbnLookupUrl + '&isbn=' + encodeURIComponent(isbn), {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        return { ok: response.ok, json: json };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.json || !result.json.success) {
                        let message = (result.json && result.json.message)
                            ? result.json.message
                            : 'ISBN lookup failed. Please try again.';
                        const providerHints = formatProviderStatusHints(result.json && result.json.provider_status);
                        if (providerHints) {
                            message += '\n\n' + providerHints;
                        }
                        alert(message);
                        return;
                    }

                    pendingLookupPayload = result.json;
                    renderLookupPreview(result.json);
                    openIsbnLookupModal();
                })
                .catch(function (err) {
                    console.error('ISBN lookup error:', err);
                    alert('ISBN lookup failed. Please check your connection and try again.');
                })
                .finally(function () {
                    setLookupButtonLoading(false);
                });
        }

        lookupBtn.addEventListener('click', runIsbnLookup);
        isbnInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                runIsbnLookup();
            }
        });

        applyBtn.addEventListener('click', function () {
            if (!pendingLookupPayload) {
                closeIsbnLookupModal();
                return;
            }
            applyIsbnLookupToForm(pendingLookupPayload);
            closeIsbnLookupModal();
        });

        [closeBtn, cancelBtn].forEach(function (button) {
            if (button) {
                button.addEventListener('click', closeIsbnLookupModal);
            }
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeIsbnLookupModal();
            }
        });
    };

    if (!window.__isbnLookupEscapeBound) {
        window.__isbnLookupEscapeBound = true;
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            const modal = document.getElementById('isbnLookupModal');
            if (modal && !modal.classList.contains('hidden') && typeof modal.__closeIsbnLookup === 'function') {
                modal.__closeIsbnLookup();
            }
        });
    }
})();
</script>
