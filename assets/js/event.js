/* ================================================================
   event.js — CampusHub Events Page Scripts
   ================================================================
   Sections
   ────────
   1. Filter Bar  — search, category & status selects, apply/reset
   2. Like Toggle — AJAX heart button (events.php & event_details.php)
   3. Save Toggle — AJAX bookmark button (same pages)
   ================================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // =================================================================
    // 1. Filter Bar
    // =================================================================

    const searchInput    = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    const statusSelect   = document.getElementById('statusSelect');
    const applyBtn       = document.getElementById('applyFilters');

    /* Highlight selects that already have an active value
       (e.g. page refreshed after a filter was applied) */
    function syncSelectState(el) {
        if (!el) return;
        el.classList.toggle('has-value', el.value !== '');
    }

    [categorySelect, statusSelect].forEach(el => {
        if (!el) return;
        syncSelectState(el);
        el.addEventListener('change', () => syncSelectState(el));
    });

    /* Build a clean URLSearchParams object and navigate */
    function applyFilters() {
        const params = new URLSearchParams();
        if (searchInput    && searchInput.value.trim())    params.set('search',   searchInput.value.trim());
        if (categorySelect && categorySelect.value)        params.set('category', categorySelect.value);
        if (statusSelect   && statusSelect.value)          params.set('status',   statusSelect.value);
        window.location.href = 'events.php?' + params.toString();
    }

    /* Apply on button click */
    if (applyBtn) {
        applyBtn.addEventListener('click', applyFilters);
    }

    /* Apply on Enter key inside the search box */
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') applyFilters();
        });
    }


    // =================================================================
    // 2. Like Toggle (Heart Button)
    // =================================================================

    /**
     * Sends an AJAX POST to like_event.php and updates the button UI.
     * Works on both events.php (card grid) and event_details.php (single view).
     * @param {HTMLElement} btn — the clicked .like-btn element
     */
    function handleLike(btn) {
        const eventId = btn.getAttribute('data-id');
        if (!eventId) return;

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('like_event.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    // Not logged in — bounce to login page
                    window.location.href = 'login.php';
                    return;
                }

                // Toggle the .liked class to update the button colour
                btn.classList.toggle('liked', data.action === 'liked');

                // Update the visible count inside the button
                const countEl = btn.querySelector('.likes-count');
                if (countEl) countEl.textContent = data.new_count;
            })
            .catch(err => console.error('Like error:', err));
    }

    /* Wire up every like button currently in the DOM */
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            handleLike(this);
        });
    });


    // =================================================================
    // 3. Save Toggle (Bookmark Button)
    // =================================================================

    /**
     * Sends an AJAX POST to saved_events.php and updates the button UI.
     * @param {HTMLElement} btn — the clicked .save-btn element
     */
    function handleSave(btn) {
        const eventId = btn.getAttribute('data-id');
        if (!eventId) return;

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('save_events.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    // Not logged in — bounce to login page
                    window.location.href = 'login.php';
                    return;
                }

                // Toggle the .saved class to update the button colour
                btn.classList.toggle('saved', data.action === 'saved');

                // Update the visible count inside the button
                const countEl = btn.querySelector('.saves-count');
                if (countEl) countEl.textContent = data.new_count;
            })
            .catch(err => console.error('Save error:', err));
    }

    /* Wire up every save button currently in the DOM */
    document.querySelectorAll('.save-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            handleSave(this);
        });
    });

});