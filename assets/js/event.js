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
    // Like Event
    // ===========================

    function handleLike(btn) {

        const eventId = btn.dataset.id;

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('like_event.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                window.location.href = 'login.php';
                return;
            }

            btn.classList.toggle(
                'liked',
                data.action === 'liked'
            );

            const count = btn.querySelector('.likes-count');

            if (count) {
                count.textContent = data.new_count;
            }
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

        const eventId = btn.dataset.id;

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('save_events.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                window.location.href = 'login.php';
                return;
            }

            btn.classList.toggle(
                'saved',
                data.action === 'saved'
            );

            const count = btn.querySelector('.saves-count');

            if (count) {
                count.textContent = data.new_count;
            }

            // Remove card from saved page
            if (data.action === 'unsaved') {

                const card =
                    btn.closest('.saved-event-card');

                if (card) {
                    card.remove();
                }
            }
        });
    }

    document.querySelectorAll('.save-btn').forEach(btn => {

        btn.addEventListener('click', function () {
            handleSave(this);
        });

    });

});