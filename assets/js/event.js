document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const categorySelect = document.getElementById('categorySelect');
    const statusSelect = document.getElementById('statusSelect');
    const eventsPage = document.querySelector('.events-page');
    let autoSearchTimer = null;
    let liveFilterRequestId = 0;
    let liveFilterController = null;
    const AUTO_SEARCH_DELAY = 350;
    const SEARCH_PREVIEW_LIMIT = 42;
    let fullSearchValue = searchInput ? searchInput.value : '';

    function toggleClearButton() {
        if (!clearSearchBtn) return;
        clearSearchBtn.classList.toggle('is-visible', fullSearchValue.trim().length > 0);
    }

    function restoreFullSearchText() {
        if (!searchInput) return;
        if (searchInput.dataset.truncated === '1') {
            searchInput.value = fullSearchValue;
            searchInput.dataset.truncated = '0';
        }
    }

    function renderTruncatedSearchText() {
        if (!searchInput) return;
        if (document.activeElement === searchInput) return;

        if (fullSearchValue.length > SEARCH_PREVIEW_LIMIT) {
            searchInput.value = fullSearchValue.slice(0, SEARCH_PREVIEW_LIMIT - 1) + '...';
            searchInput.dataset.truncated = '1';
            searchInput.title = fullSearchValue;
        } else {
            searchInput.value = fullSearchValue;
            searchInput.dataset.truncated = '0';
            searchInput.title = '';
        }
    }

    function syncSelectState(el) {
        if (!el) return;
        el.classList.toggle('has-value', el.value !== '');
    }

    [categorySelect, statusSelect].forEach(el => {
        if (!el) return;
        syncSelectState(el);
        el.addEventListener('change', () => {
            syncSelectState(el);
            applyFilters({ updateHistory: true });
        });
    });

    function buildFilterParams() {

        const params = new URLSearchParams();

        const searchValue = fullSearchValue.trim();

        if (searchValue) {
            params.set('search', searchValue);
        }

        if (categorySelect && categorySelect.value) {
            params.set('category', categorySelect.value);
        }

        if (statusSelect && statusSelect.value) {
            params.set('status', statusSelect.value);
        }

        return params;
    }

    function updateActivePills(doc) {
        if (!eventsPage) return;

        const currentPills = eventsPage.querySelector('.active-filter-pills');
        const nextPills = doc.querySelector('.events-page .active-filter-pills');
        const filterBar = eventsPage.querySelector('.filter-bar');

        if (currentPills && !nextPills) {
            currentPills.remove();
            return;
        }

        if (!currentPills && nextPills && filterBar) {
            filterBar.insertAdjacentElement('afterend', nextPills.cloneNode(true));
            return;
        }

        if (currentPills && nextPills) {
            currentPills.outerHTML = nextPills.outerHTML;
        }
    }

    function renderFilteredPage(doc) {
        if (!eventsPage) return;

        const currentGrid = eventsPage.querySelector('.events-grid');
        const nextGrid = doc.querySelector('.events-page .events-grid');
        const currentCount = eventsPage.querySelector('.events-count-badge');
        const nextCount = doc.querySelector('.events-page .events-count-badge');

        if (currentGrid && nextGrid) {
            currentGrid.innerHTML = nextGrid.innerHTML;
        }

        if (currentCount && nextCount) {
            currentCount.textContent = nextCount.textContent;
        }

        updateActivePills(doc);
    }

    async function applyFilters(options = {}) {
        if (!eventsPage) return;

        const { updateHistory = true } = options;
        const params = buildFilterParams();
        const queryString = params.toString();
        const nextUrl = 'events.php' + (queryString ? '?' + queryString : '');
        const requestId = ++liveFilterRequestId;

        if (liveFilterController) {
            liveFilterController.abort();
        }
        liveFilterController = new AbortController();

        try {
            const response = await fetch(nextUrl, {
                signal: liveFilterController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load events');
            }

            const html = await response.text();

            if (requestId !== liveFilterRequestId) {
                return;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            renderFilteredPage(doc);

            if (updateHistory) {
                history.replaceState({}, '', nextUrl);
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('[events] Live filter failed:', err);
            }
        }

        renderTruncatedSearchText();
    }

    function queueAutoSearch() {
        if (autoSearchTimer) {
            clearTimeout(autoSearchTimer);
        }
        autoSearchTimer = setTimeout(() => {
            applyFilters({ updateHistory: true });
        }, AUTO_SEARCH_DELAY);
    }

    if (searchInput) {
        toggleClearButton();
        renderTruncatedSearchText();

        searchInput.addEventListener('focus', function () {
            restoreFullSearchText();
        });

        searchInput.addEventListener('blur', function () {
            renderTruncatedSearchText();
        });

        searchInput.addEventListener('input', function () {
            fullSearchValue = this.value;
            toggleClearButton();
            queueAutoSearch();
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters({ updateHistory: true });
            }
        });
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', function () {
            if (autoSearchTimer) {
                clearTimeout(autoSearchTimer);
            }

            fullSearchValue = '';
            searchInput.value = '';
            searchInput.dataset.truncated = '0';
            searchInput.title = '';
            toggleClearButton();
            searchInput.focus();

            const suggestionBox = document.getElementById('searchSuggestions');
            if (suggestionBox) {
                suggestionBox.style.display = 'none';
            }

            applyFilters({ updateHistory: true });
        });
    }

    // ===========================
    // Search Suggestions
    // ===========================

    const suggestionBox = document.getElementById('searchSuggestions');

    if (searchInput && suggestionBox) {

        searchInput.addEventListener('input', function () {

            const value = this.value.toLowerCase().trim();

            if (value.length < 1) {
                suggestionBox.style.display = 'none';
                return;
            }

            fetch('search_events.php?q=' + encodeURIComponent(value))
                .then(res => res.json())
                .then(events => {

                    suggestionBox.innerHTML = '';

                    const limitedEvents = Array.isArray(events) ? events.slice(0, 6) : [];

                    if (!limitedEvents.length) {
                        suggestionBox.style.display = 'none';
                        return;
                    }

                    limitedEvents.forEach(event => {

                        const item = document.createElement('div');

                        item.className = 'search-suggestion-item';

                        item.innerHTML = `
                            <strong>${event.title}</strong>
                            <br>
                            <small>${event.society_name}</small>
                        `;

                        item.addEventListener('click', function () {

                            window.location.href =
                                'event_details.php?id=' + event.id;
                        });

                        suggestionBox.appendChild(item);
                    });

                    suggestionBox.style.display = 'block';
                });
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                suggestionBox.style.display = 'none';
            }
        });

        document.addEventListener('click', function (e) {

            if (
                !searchInput.contains(e.target) &&
                !suggestionBox.contains(e.target)
            ) {
                suggestionBox.style.display = 'none';
            }
        });
    }

    if (eventsPage) {
        eventsPage.addEventListener('click', function (e) {
            const pillLink = e.target.closest('.active-filter-pills a');
            if (!pillLink) return;

            e.preventDefault();
            const url = new URL(pillLink.href, window.location.origin);
            const nextParams = url.searchParams;

            fullSearchValue = nextParams.get('search') || '';

            if (searchInput) {
                searchInput.value = fullSearchValue;
                searchInput.dataset.truncated = '0';
            }

            if (categorySelect) {
                categorySelect.value = nextParams.get('category') || '';
                syncSelectState(categorySelect);
            }

            if (statusSelect) {
                statusSelect.value = nextParams.get('status') || '';
                syncSelectState(statusSelect);
            }

            toggleClearButton();
            applyFilters({ updateHistory: true });
        });
    }

    // ===========================
    // Shared helper: toast-style inline error
    // ===========================

    function showActionError(btn, message) {
        // Brief, non-blocking inline message instead of a disruptive alert().
        let toast = document.createElement('span');
        toast.className = 'action-error-toast';
        toast.textContent = message;
        btn.parentElement.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    // ===========================
    // Like Event
    // ===========================

    function handleLike(btn) {

        // Prevent double-fire from a second click while a request is still in flight.
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';
        btn.classList.add('is-loading');

        const eventId = btn.dataset.id;
        const wasLiked = btn.classList.contains('liked');
        const countEl = btn.querySelector('.likes-count');
        const previousCount = countEl ? countEl.textContent : null;

        // Optimistic UI update so the click feels instant.
        btn.classList.toggle('liked', !wasLiked);
        if (countEl) {
            const current = parseInt(previousCount, 10) || 0;
            countEl.textContent = wasLiked ? Math.max(0, current - 1) : current + 1;
        }

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('like_event.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                // Roll back the optimistic update.
                btn.classList.toggle('liked', wasLiked);
                if (countEl && previousCount !== null) {
                    countEl.textContent = previousCount;
                }

                if (data.message === 'Please login') {
                    window.location.href = 'login.php';
                    return;
                }

                showActionError(btn, data.message || 'Could not update like.');
                return;
            }

            // Reconcile with the server's authoritative values.
            btn.classList.toggle('liked', data.action === 'liked');
            if (countEl) {
                countEl.textContent = data.new_count;
            }
        })
        .catch(() => {
            btn.classList.toggle('liked', wasLiked);
            if (countEl && previousCount !== null) {
                countEl.textContent = previousCount;
            }
            showActionError(btn, 'Network error. Please try again.');
        })
        .finally(() => {
            btn.dataset.busy = '0';
            btn.classList.remove('is-loading');
        });
    }

    // ===========================
    // Save Event
    // ===========================

    function handleSave(btn) {

        // Prevent double-fire from a second click while a request is still in flight.
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';
        btn.classList.add('is-loading');

        const eventId = btn.dataset.id;
        const wasSaved = btn.classList.contains('saved');
        const countEl = btn.querySelector('.saves-count');
        const previousCount = countEl ? countEl.textContent : null;

        // Optimistic UI update so Save/Unsave feels instant, like Instagram's bookmark toggle.
        btn.classList.toggle('saved', !wasSaved);
        if (countEl) {
            const current = parseInt(previousCount, 10) || 0;
            countEl.textContent = wasSaved ? Math.max(0, current - 1) : current + 1;
        }

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('save_events.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                // Roll back the optimistic update.
                btn.classList.toggle('saved', wasSaved);
                if (countEl && previousCount !== null) {
                    countEl.textContent = previousCount;
                }

                if (data.message === 'Please login') {
                    window.location.href = 'login.php';
                    return;
                }

                showActionError(btn, data.message || 'Could not update saved status.');
                return;
            }

            // Reconcile with the server's authoritative values.
            btn.classList.toggle('saved', data.action === 'saved');
            if (countEl) {
                countEl.textContent = data.new_count;
            }

            // On the dedicated Saved Events page, unsaving removes the card immediately.
            if (data.action === 'unsaved') {

                const card = btn.closest('.saved-event-card');

                if (card) {
                    card.style.transition = 'opacity 0.25s ease';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 250);
                }
            }
        })
        .catch(() => {
            btn.classList.toggle('saved', wasSaved);
            if (countEl && previousCount !== null) {
                countEl.textContent = previousCount;
            }
            showActionError(btn, 'Network error. Please try again.');
        })
        .finally(() => {
            btn.dataset.busy = '0';
            btn.classList.remove('is-loading');
        });
    }

    document.addEventListener('click', function (e) {
        const likeBtn = e.target.closest('.like-btn');
        if (likeBtn) {
            e.preventDefault();
            handleLike(likeBtn);
            return;
        }

        const saveBtn = e.target.closest('.save-btn');
        if (saveBtn) {
            e.preventDefault();
            handleSave(saveBtn);
        }
    });

});