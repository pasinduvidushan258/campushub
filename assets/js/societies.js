document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('societySearch');
    const searchForm = document.getElementById('societySearchForm');
    const clearBtn = document.getElementById('clearSocietySearch');
    const eventsPage = document.querySelector('.events-page');
    let searchTimer = null;
    let liveRequestId = 0;
    let liveController = null;

    function toggleClearButton() {
        if (!clearBtn || !searchInput) {
            return;
        }
        clearBtn.classList.toggle('is-visible', searchInput.value.trim().length > 0);
    }

    function buildSearchUrl() {
        const params = new URLSearchParams();
        if (searchInput && searchInput.value.trim()) {
            params.set('search', searchInput.value.trim());
        }

        const query = params.toString();
        return 'societies.php' + (query ? '?' + query : '');
    }

    function renderSocietiesPage(doc) {
        if (!eventsPage) {
            return;
        }

        const currentGrid = eventsPage.querySelector('.society-grid');
        const nextGrid = doc.querySelector('.events-page .society-grid');
        const currentCount = eventsPage.querySelector('.events-count-badge');
        const nextCount = doc.querySelector('.events-page .events-count-badge');
        const filterBar = eventsPage.querySelector('.filter-bar');
        const currentPills = eventsPage.querySelector('.active-filter-pills');
        const nextPills = doc.querySelector('.events-page .active-filter-pills');

        if (currentGrid && nextGrid) {
            currentGrid.innerHTML = nextGrid.innerHTML;
        }

        if (currentCount && nextCount) {
            currentCount.textContent = nextCount.textContent;
        }

        if (currentPills && !nextPills) {
            currentPills.remove();
        } else if (!currentPills && nextPills && filterBar) {
            filterBar.insertAdjacentElement('afterend', nextPills.cloneNode(true));
        } else if (currentPills && nextPills) {
            currentPills.outerHTML = nextPills.outerHTML;
        }
    }

    function applySearch(options) {
        if (!eventsPage) {
            return;
        }

        const settings = options || {};
        const updateHistory = settings.updateHistory !== false;
        const nextUrl = buildSearchUrl();
        const requestId = ++liveRequestId;

        if (liveController) {
            liveController.abort();
        }

        liveController = new AbortController();

        fetch(nextUrl, {
            signal: liveController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Failed to load societies');
            }
            return response.text();
        })
        .then(function (html) {
            if (requestId !== liveRequestId) {
                return;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            renderSocietiesPage(doc);

            if (updateHistory) {
                history.replaceState({}, '', nextUrl);
            }
        })
        .catch(function (err) {
            if (err.name !== 'AbortError') {
                console.error('[societies] Live search failed:', err);
            }
        });
    }

    if (searchInput && searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            applySearch({ updateHistory: true });
        });

        searchInput.addEventListener('input', function () {
            toggleClearButton();

            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            searchTimer = setTimeout(function () {
                applySearch({ updateHistory: true });
            }, 450);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }
                applySearch({ updateHistory: true });
            }
        });

        toggleClearButton();
    }

    if (clearBtn && searchInput && searchForm) {
        clearBtn.addEventListener('click', function () {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            searchInput.value = '';
            clearBtn.classList.remove('is-visible');
            applySearch({ updateHistory: true });
            searchInput.focus();
        });
    }

    if (eventsPage) {
        eventsPage.addEventListener('click', function (e) {
            const clearPillLink = e.target.closest('.active-filter-pills a');
            if (!clearPillLink) {
                return;
            }

            e.preventDefault();

            if (searchInput) {
                searchInput.value = '';
            }
            toggleClearButton();

            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            applySearch({ updateHistory: true });
        });
    }

    function showFollowError(button, message) {
        const parent = button.closest('.society-card-actions') || button.parentElement;
        if (!parent) {
            return;
        }

        const oldToast = parent.querySelector('.action-error-toast');
        if (oldToast) {
            oldToast.remove();
        }

        const toast = document.createElement('span');
        toast.className = 'action-error-toast';
        toast.textContent = message;
        parent.appendChild(toast);

        setTimeout(function () {
            toast.remove();
        }, 2400);
    }

    document.addEventListener('click', function (e) {
        const followBtn = e.target.closest('.soc-follow-btn');
        if (!followBtn) {
            return;
        }

        e.preventDefault();

        if (followBtn.classList.contains('is-loading')) {
            return;
        }

        const societyId = followBtn.dataset.id;
        if (!societyId) {
            return;
        }

        followBtn.classList.add('is-loading');

        const formData = new FormData();
        formData.append('society_id', societyId);

        fetch('follow_society.php', {
            method: 'POST',
            body: formData
        })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (!data.success) {
                if ((data.message || '').toLowerCase().includes('log in')) {
                    window.location.href = 'login.php';
                    return;
                }
                showFollowError(followBtn, data.message || 'Could not update follow status.');
                return;
            }

            followBtn.classList.toggle('is-following', !!data.following);

            const icon = followBtn.querySelector('i');
            const label = followBtn.querySelector('.follow-label');

            if (icon) {
                icon.className = data.following ? 'fas fa-check' : 'fas fa-plus';
            }
            if (label) {
                label.textContent = data.following ? 'Following' : 'Follow';
            }

            const card = followBtn.closest('.society-card');
            const followerCount = card ? card.querySelector('.soc-followers-count') : null;
            if (followerCount) {
                followerCount.textContent = data.followers;
            }
        })
        .catch(function () {
            showFollowError(followBtn, 'Network error. Please try again.');
        })
        .finally(function () {
            followBtn.classList.remove('is-loading');
        });
    });
});
