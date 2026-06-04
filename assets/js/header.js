document.addEventListener('DOMContentLoaded', function() {

    const profileBtn      = document.getElementById('profileDropdownBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const viewMain        = document.getElementById('profileViewMain');
    const viewAll         = document.getElementById('profileViewAll');

    // Close all open dropdowns and reset to the main view after the CSS transition completes
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

    // Toggle the profile dropdown on button click; close it first if already open
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isShowing = profileDropdown.classList.contains('show');
            closeAllDropdowns();
            if (!isShowing) profileDropdown.classList.add('show');
        });
    }

    // Close the dropdown when clicking anywhere outside of it
    document.addEventListener('click', closeAllDropdowns);

    // Prevent clicks inside the dropdown from bubbling up and triggering the close handler
    if (profileDropdown) profileDropdown.addEventListener('click', e => e.stopPropagation());

    // Navigation between the two dropdown views
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

    // Profile switch handler — pending societies redirect to an info page instead of switching
    const switchBtns = document.querySelectorAll('.quick-switch-btn');
    switchBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.getAttribute('data-status');

            if (status === 'pending') {
                // Society is not yet verified — redirect to the pending status page
                window.location.href = 'society_pending.php';
            } else {
                // Verified society — show a loading spinner, then perform the role switch
                const icon = this.querySelector('.switchIcon');
                if (icon) icon.classList.add('spinning-loader');

                const targetType = this.getAttribute('data-type');
                const targetId   = this.getAttribute('data-id');

                setTimeout(() => {
                    window.location.href = 'switch_role.php?type=' + targetType + '&id=' + targetId;
                }, 1000);
            }
        });
    });

});