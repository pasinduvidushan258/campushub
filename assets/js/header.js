document.addEventListener('DOMContentLoaded', function() {

    const profileBtn      = document.getElementById('profileDropdownBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const viewMain        = document.getElementById('profileViewMain');
    const viewAll         = document.getElementById('profileViewAll');

    function closeAllDropdowns() {
        if (profileDropdown) {
            profileDropdown.classList.remove('show');
            setTimeout(() => {
                if (viewMain && viewAll) {
                    viewMain.style.display = 'block';
                    viewAll.style.display  = 'none';
                }
            }, 300);
        }
    }

    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isShowing = profileDropdown.classList.contains('show');
            closeAllDropdowns();
            if (!isShowing) profileDropdown.classList.add('show');
        });
    }

    document.addEventListener('click', closeAllDropdowns);
    if (profileDropdown) profileDropdown.addEventListener('click', e => e.stopPropagation());

    const seeAllBtn = document.getElementById('seeAllProfilesBtn');
    const backBtn   = document.getElementById('backToMainProfileBtn');

    if (seeAllBtn && backBtn && viewMain && viewAll) {
        seeAllBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            viewMain.style.display = 'none';
            viewAll.style.display  = 'block';
        });
        backBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            viewAll.style.display  = 'none';
            viewMain.style.display = 'block';
        });
    }

    // ============================================================
    // Advanced Facebook-Style Switch Handler
    // ============================================================
    const switchBtns = document.querySelectorAll('.quick-switch-btn');
    switchBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); 
            
            const status = this.getAttribute('data-status');

            if (status === 'pending') {
                window.location.href = 'society_pending.php';
                return;
            }

            const targetType = this.getAttribute('data-type');
            const targetId   = this.getAttribute('data-id');
            
            // get name and avatar for the loading screen
            const nameElement = this.querySelector('.fb-profile-name');
            const targetName = nameElement ? nameElement.innerText.trim() : 'Profile';

            // get avatar HTML (could be an image or an icon)
            const avatarElement = this.querySelector('.fb-avatar-circle');
            let avatarHTML = '<i class="fas fa-user"></i>'; 
            
            if (avatarElement) {
                avatarHTML = avatarElement.innerHTML; 
            }

            // show the switching overlay immediately with the target name and avatar
            showSwitchingOverlay(targetName, avatarHTML);

            const formData = new FormData();
            formData.append('type', targetType);
            formData.append('id', targetId);

            fetch('switch_role.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500); 
                } else {
                    alert('Error: ' + data.message);
                    removeOverlay();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Something went wrong!");
                removeOverlay();
            });
        });
    });

    function showSwitchingOverlay(name, avatarHTML) {
        const overlay = document.createElement('div');
        overlay.id = 'fb-switching-overlay';
        overlay.innerHTML = `
            <div class="switching-content">
                <div class="switching-avatar-wrapper">
                    <div class="switching-spinner"></div>
                    <div class="switching-avatar">${avatarHTML}</div>
                </div>
                <h2 class="switching-text">Switching to ${name}...</h2>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    function removeOverlay() {
        const overlay = document.getElementById('fb-switching-overlay');
        if (overlay) overlay.remove();
    }

    // ============================================================
    // Global Header Search — live suggestions across modules
    // ============================================================
    const headerSearchInput = document.getElementById('headerSearchInput');
    const headerSearchPanel = document.getElementById('headerSearchPanel');

    if (headerSearchInput && headerSearchPanel) {
        let debounceTimer = null;
        let fetchController = null;
        let latestRequestId = 0;
        let activeIndex = -1;

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function highlightMatch(text, query) {
            if (!query) return escapeHtml(text);
            const safeText = String(text);
            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp('(' + escapedQuery + ')', 'ig');
            return escapeHtml(safeText).replace(regex, '<mark>$1</mark>');
        }

        function openSearchPanel() {
            headerSearchPanel.classList.add('is-open');
            headerSearchInput.setAttribute('aria-expanded', 'true');
        }

        function closeSearchPanel() {
            headerSearchPanel.classList.remove('is-open');
            headerSearchInput.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }

        function getSuggestionItems() {
            return Array.from(headerSearchPanel.querySelectorAll('.header-search-item'));
        }

        function setActiveItem(index) {
            const items = getSuggestionItems();
            if (!items.length) {
                activeIndex = -1;
                return;
            }

            if (index < 0) index = items.length - 1;
            if (index >= items.length) index = 0;
            activeIndex = index;

            items.forEach((item, idx) => {
                item.classList.toggle('is-active', idx === activeIndex);
                item.setAttribute('aria-selected', idx === activeIndex ? 'true' : 'false');
            });

            const activeItem = items[activeIndex];
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'nearest' });
            }
        }

        function renderSections(payload, query) {
            const sections = [
                { key: 'events', label: 'Events', icon: 'fa-calendar-alt' },
                { key: 'societies', label: 'Societies', icon: 'fa-users' },
                { key: 'notices', label: 'Notices', icon: 'fa-bullhorn' },
                { key: 'users', label: 'People', icon: 'fa-user' }
            ];

            const sectionMarkup = sections.map(section => {
                const items = Array.isArray(payload[section.key]) ? payload[section.key] : [];
                if (!items.length) return '';

                const rows = items.map(item => {
                    const title = highlightMatch(item.title || '', query);
                    const subtitle = highlightMatch(item.subtitle || '', query);
                    const url = escapeHtml(item.url || '#');

                    return `
                        <button type="button" class="header-search-item" role="option" aria-selected="false" data-url="${url}">
                            <span class="header-search-item-icon"><i class="fas ${section.icon}"></i></span>
                            <span class="header-search-item-copy">
                                <strong>${title}</strong>
                                <small>${subtitle}</small>
                            </span>
                            <span class="header-search-item-arrow"><i class="fas fa-arrow-up-right-from-square"></i></span>
                        </button>
                    `;
                }).join('');

                return `
                    <div class="header-search-group">
                        <div class="header-search-group-title">${section.label}</div>
                        ${rows}
                    </div>
                `;
            }).join('');

            if (!sectionMarkup) {
                headerSearchPanel.innerHTML = `
                    <div class="header-search-empty">
                        <i class="fas fa-search"></i>
                        <span>No matches found for "${escapeHtml(query)}"</span>
                    </div>
                `;
                openSearchPanel();
                return;
            }

            headerSearchPanel.innerHTML = `
                <div class="header-search-scroll">${sectionMarkup}</div>
                <button type="button" class="header-search-all" data-url="events.php?search=${encodeURIComponent(query)}">
                    View all events for "${escapeHtml(query)}"
                </button>
            `;
            openSearchPanel();
        }

        function bindSearchPanelActions() {
            headerSearchPanel.querySelectorAll('[data-url]').forEach(el => {
                el.addEventListener('click', function () {
                    const url = this.getAttribute('data-url');
                    if (url) {
                        window.location.href = url;
                    }
                });
            });
        }

        function showLoadingState() {
            headerSearchPanel.innerHTML = `
                <div class="header-search-loading">
                    <span class="search-pulse"></span>
                    <span class="search-pulse"></span>
                    <span class="search-pulse"></span>
                </div>
            `;
            openSearchPanel();
        }

        function fetchGlobalSearch(query) {
            if (!query) {
                closeSearchPanel();
                return;
            }

            if (fetchController) {
                fetchController.abort();
            }

            const requestId = ++latestRequestId;
            fetchController = new AbortController();

            showLoadingState();

            fetch('search_global.php?q=' + encodeURIComponent(query), {
                signal: fetchController.signal
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }
                    return response.json();
                })
                .then(payload => {
                    if (requestId !== latestRequestId) {
                        return;
                    }

                    renderSections(payload || {}, query);
                    bindSearchPanelActions();
                    setActiveItem(0);
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    headerSearchPanel.innerHTML = `
                        <div class="header-search-empty">
                            <i class="fas fa-circle-exclamation"></i>
                            <span>Search is temporarily unavailable.</span>
                        </div>
                    `;
                    openSearchPanel();
                });
        }

        function queueSearch() {
            const query = headerSearchInput.value.trim();

            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }

            debounceTimer = setTimeout(() => {
                fetchGlobalSearch(query);
            }, 220);
        }

        headerSearchInput.addEventListener('input', queueSearch);

        headerSearchInput.addEventListener('focus', function () {
            if (headerSearchInput.value.trim()) {
                queueSearch();
            }
        });

        headerSearchInput.addEventListener('keydown', function (e) {
            const items = getSuggestionItems();

            if (e.key === 'ArrowDown' && items.length) {
                e.preventDefault();
                setActiveItem(activeIndex + 1);
                return;
            }

            if (e.key === 'ArrowUp' && items.length) {
                e.preventDefault();
                setActiveItem(activeIndex - 1);
                return;
            }

            if (e.key === 'Escape') {
                closeSearchPanel();
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                const query = headerSearchInput.value.trim();

                if (activeIndex >= 0 && items[activeIndex]) {
                    const selectedUrl = items[activeIndex].getAttribute('data-url');
                    if (selectedUrl) {
                        window.location.href = selectedUrl;
                        return;
                    }
                }

                if (query) {
                    window.location.href = 'events.php?search=' + encodeURIComponent(query);
                }
            }
        });

        document.addEventListener('click', function (event) {
            const clickInsideInput = headerSearchInput.contains(event.target);
            const clickInsidePanel = headerSearchPanel.contains(event.target);

            if (!clickInsideInput && !clickInsidePanel) {
                closeSearchPanel();
            }
        });
    }

});