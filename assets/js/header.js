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
            
            // 1. නම ලබා ගැනීම
            const nameElement = this.querySelector('.fb-profile-name');
            const targetName = nameElement ? nameElement.innerText.trim() : 'Profile';

            // 2. පින්තූරය (Avatar) ලබා ගැනීම
            const avatarElement = this.querySelector('.fb-avatar-circle');
            let avatarHTML = '<i class="fas fa-user"></i>'; 
            
            if (avatarElement) {
                avatarHTML = avatarElement.innerHTML; 
            }

            // 3. ලෝඩින් ස්ක්‍රීන් එක පෙන්වීම 
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

});
// Header search functionality
const headerSearch = document.getElementById('headerSearchInput');
if (headerSearch) {
    headerSearch.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = encodeURIComponent(this.value.trim());
            if (query) {
                window.location.href = 'events.php?search=' + query;
            }
        }
    });
}