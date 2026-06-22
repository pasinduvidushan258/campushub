document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    const statusSelect = document.getElementById('statusSelect');
    const applyBtn = document.getElementById('applyFilters');

    function syncSelectState(el) {
        if (!el) return;
        el.classList.toggle('has-value', el.value !== '');
    }

    [categorySelect, statusSelect].forEach(el => {
        if (!el) return;
        syncSelectState(el);
        el.addEventListener('change', () => syncSelectState(el));
    });

    function applyFilters() {

        const params = new URLSearchParams();

        if (searchInput && searchInput.value.trim()) {
            params.set('search', searchInput.value.trim());
        }

        if (categorySelect && categorySelect.value) {
            params.set('category', categorySelect.value);
        }

        if (statusSelect && statusSelect.value) {
            params.set('status', statusSelect.value);
        }

        window.location.href = 'events.php?' + params.toString();
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', applyFilters);
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
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

                    if (!events.length) {
                        suggestionBox.style.display = 'none';
                        return;
                    }

                    events.forEach(event => {

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

        document.addEventListener('click', function (e) {

            if (
                !searchInput.contains(e.target) &&
                !suggestionBox.contains(e.target)
            ) {
                suggestionBox.style.display = 'none';
            }
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

    document.querySelectorAll('.like-btn').forEach(btn => {

        btn.addEventListener('click', function () {
            handleLike(this);
        });

    });

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

    document.querySelectorAll('.save-btn').forEach(btn => {

        btn.addEventListener('click', function () {
            handleSave(this);
        });

    });

});