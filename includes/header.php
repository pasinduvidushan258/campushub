<?php
// Start a session only if one is not already active (prevents "headers already sent" errors)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the current active mode — defaults to 'user' if not set in session
$active_mode = $_SESSION['active_mode'] ?? 'user';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusHub</title>

    <!-- Google Fonts — Inter typeface for consistent UI typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome — icon library used throughout the header -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Modular header stylesheets — split by component for maintainability -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header-main.css">
    <link rel="stylesheet" href="assets/css/header-hamburger.css">
    <link rel="stylesheet" href="assets/css/header-dropdown.css">
    <link rel="stylesheet" href="assets/css/header-responsive.css">
    <link rel="stylesheet" href="/campushub/assets/css/switch-loader.css">
    <?php if (!empty($extra_stylesheets) && is_array($extra_stylesheets)): ?>
        <?php foreach ($extra_stylesheets as $stylesheet): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($stylesheet); ?>">
        <?php endforeach; ?>
    <?php endif; ?> 
</head>
<body>

<header class="main-header">
    <div class="header-container">

        <!-- Left section: logo and search bar -->
        <div class="header-left">
            <a href="index.php" class="logo">
                <img src="assets/images/campushub_logo.jpg" alt="Logo" class="logo-img">
                CampusHub
            </a>
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="headerSearchInput" placeholder="Search events...">
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- Centre navigation: primary site links                -->
        <!-- Active class is set based on the current script name -->
        <!-- ===================================================== -->
        <nav class="header-center">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <a href="index.php"      class="nav-btn <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" title="Home">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="events.php"     class="nav-btn <?php echo ($current_page == 'events.php') ? 'active' : ''; ?>" title="Events">
                <i class="fas fa-calendar-alt"></i> Events
            </a>
            <a href="notices.php"    class="nav-btn <?php echo ($current_page == 'notices.php') ? 'active' : ''; ?>" title="Notices">
                <i class="fas fa-thumbtack"></i> Notices
            </a>
            <a href="societies.php"  class="nav-btn <?php echo ($current_page == 'societies.php') ? 'active' : ''; ?>" title="Societies">
                <i class="fas fa-users"></i> Societies
            </a>
        </nav>

        <!-- Right section: action icons and profile menu (authenticated users only) -->
        <div class="header-right">

            <?php if(isset($_SESSION['user_id'])): ?>

                <!-- Quick-action icon buttons: messages and notifications -->
                <div class="icon-group">
                    <div class="nav-item-container">
                        <button class="icon-btn" id="msgBtn" title="Messages"><i class="fas fa-envelope"></i></button>
                    </div>
                    <div class="nav-item-container">
                        <button class="icon-btn" id="notifBtn" title="Notifications"><i class="fas fa-bell"></i></button>
                    </div>
                </div>

                <div class="profile-menu-container">

                    <?php
                    require_once 'config/database.php';
                    
                    // Default avatar icon if no custom avatar is set — uses a Font Awesome user icon with a neutral color
                    $user_avatar_tag = '<i class="fas fa-user" style="color: #b0b3b8;"></i>'; 
                    
                    if (isset($pdo) && isset($_SESSION['user_id'])) {
                        $u_stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
                        $u_stmt->execute([$_SESSION['user_id']]);
                        $u_data = $u_stmt->fetch();
                        
                        // Check if the user has an avatar URL in the database; if not, fall back to any avatar URL stored in the session (from a recent upload), or use the default icon if neither is available
                        $db_avatar = $u_data['avatar_url'] ?? ($_SESSION['avatar_url'] ?? '');
                        
                        // If the user has uploaded a custom avatar, use it
                        if (!empty($db_avatar) && $db_avatar !== 'assets/images/default_avatar.png') {
                            $_SESSION['avatar_url'] = $db_avatar; 
                            
                            // Construct the HTML for the user's avatar image, ensuring it is styled as a circle and fits within the profile button
                            $user_avatar_tag = '<img src="/campushub/' . htmlspecialchars($db_avatar) . '" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block;">';
                        }
                    }

                    $society_avatar_tag = '<i class="fas fa-users"></i>';
                    if (
                        isset($pdo)
                        && $active_mode === 'society'
                        && !empty($_SESSION['active_society_id'])
                    ) {
                        $active_soc_stmt = $pdo->prepare("SELECT logo_path FROM societies WHERE id = ? LIMIT 1");
                        $active_soc_stmt->execute([(int) $_SESSION['active_society_id']]);
                        $active_soc_data = $active_soc_stmt->fetch();
                        $active_soc_logo = trim((string) ($active_soc_data['logo_path'] ?? ''));

                        if ($active_soc_logo !== '' && file_exists('assets/images/uploads/' . $active_soc_logo)) {
                            $society_avatar_tag = '<img src="/campushub/assets/images/uploads/' . htmlspecialchars($active_soc_logo) . '" alt="Society" class="avatar-thumb">';
                        }
                    }

                    // Determine which avatar to show in the top-right profile button based on the active mode
                    $top_avatar = ($active_mode === 'society') ? $society_avatar_tag : $user_avatar_tag;
                    ?>

                    

                    <!-- Profile dropdown trigger button -->
                    <button class="profile-btn" id="profileDropdownBtn">
                        <div class="profile-avatar"><?php echo $top_avatar; ?></div>
                        <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown" style="width: 320px;">

                        <?php
                        require_once 'config/database.php';
                        $has_society  = false;
                        $all_societies = [];

                        if (isset($pdo) && isset($_SESSION['user_id'])) {
                            // Fetch all societies owned by the current user, including pending ones
                            $soc_stmt = $pdo->prepare("SELECT * FROM societies WHERE admin_id = ?");
                            $soc_stmt->execute([$_SESSION['user_id']]);
                            $all_societies = $soc_stmt->fetchAll();
                            $has_society   = (count($all_societies) > 0);
                        }

                        $build_society_avatar = function ($soc) {
                            $logo = trim((string) ($soc['logo_path'] ?? ''));
                            if ($logo !== '' && file_exists('assets/images/uploads/' . $logo)) {
                                return '<img src="/campushub/assets/images/uploads/' . htmlspecialchars($logo) . '" alt="Society" class="avatar-thumb">';
                            }
                            return '<i class="fas fa-users"></i>';
                        };

                        // Resolve the display name and icon for the currently active profile
                        $active_name = $_SESSION['fullname'];
                        $active_icon = $user_avatar_tag;
                        if ($active_mode === 'society' && isset($_SESSION['active_society_name'])) {
                            $active_name = $_SESSION['active_society_name'];
                            $active_icon = $society_avatar_tag;
                        }
                        ?>

                        <!-- ============================================================ -->
                        <!-- VIEW 1: Main dropdown — shows active profile and quick switch -->
                        <!-- ============================================================ -->
                        <div id="profileViewMain">
                            <div class="fb-profile-box-card">

                                <!-- Currently active profile row with a checkmark indicator -->
                                <div class="fb-profile-row active-profile">
                                    <div class="fb-avatar-circle"><?php echo $active_icon; ?></div>
                                    <span class="fb-profile-name"><?php echo htmlspecialchars($active_name); ?></span>
                                    <i class="fas fa-check-circle fb-active-tick"></i>
                                </div>

                                <?php if($active_mode === 'society'): ?>
                                    <!-- Currently in society mode — show personal profile as the switch target -->
                                    <div class="fb-profile-row switch-profile-item quick-switch-btn" data-type="user" data-id="0" data-status="verified">
                                        <div class="fb-avatar-circle"><?php echo $user_avatar_tag; ?></div>
                                        <span class="fb-profile-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                                        <div class="fb-switch-icon-wrapper"><i class="fas fa-sync-alt switchIcon"></i></div>
                                    </div>

                                <?php else: ?>
                                    <?php if($has_society): ?>
                                        <?php
                                        // Display the first society as a quick-switch option
                                        $first_soc        = $all_societies[0];
                                        $is_first_pending = ($first_soc['status'] === 'pending');
                                        $first_soc_avatar = $build_society_avatar($first_soc);
                                        ?>
                                        <div class="fb-profile-row switch-profile-item quick-switch-btn"
                                             data-type="society"
                                             data-id="<?php echo $first_soc['id']; ?>"
                                             data-status="<?php echo $first_soc['status']; ?>">
                                            <div class="fb-avatar-circle society-avatar <?php echo strpos($first_soc_avatar, '<img') !== false ? 'has-image' : ''; ?>"><?php echo $first_soc_avatar; ?></div>
                                            <span class="fb-profile-name">
                                                <?php echo htmlspecialchars($first_soc['society_name']); ?>
                                                <?php if($is_first_pending): ?>
                                                    <!-- Badge shown when the society is awaiting verification -->
                                                    <span style="font-size: 0.65rem; background: #e41e3f; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">Pending</span>
                                                <?php endif; ?>
                                            </span>
                                            <div class="fb-switch-icon-wrapper">
                                                <?php if($is_first_pending): ?>
                                                    <i class="fas fa-exclamation-circle" style="color: #e41e3f;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sync-alt switchIcon"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    <?php else: ?>
                                        <!-- No society exists — prompt the user to create one -->
                                        <a href="create_society.php" style="text-decoration: none;">
                                            <div class="fb-profile-row" style="background: rgba(249, 115, 22, 0.05); margin-top: 5px;">
                                                <div class="fb-avatar-circle" style="background: rgba(249, 115, 22, 0.15); color: #F97316;"><i class="fas fa-plus"></i></div>
                                                <span class="fb-profile-name" style="color: #F97316; margin-left: 10px;">Create a Society</span>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Link to the full profile list view -->
                                <div class="fb-see-all-btn" id="seeAllProfilesBtn" style="cursor: pointer;">
                                    <i class="fas fa-th-large" style="margin-right: 6px;"></i> See all profiles
                                </div>
                            </div>

                            <div class="dropdown-divider"></div>

                            <!-- ============================================================ -->
                            <!-- Role-Based Dynamic Menu Items (Student vs Society)           -->
                            <!-- ============================================================ -->
                            <?php if ($active_mode === 'society'): ?>
                                <!-- Society Menu Links -->
                                <a href="society_dashboard.php" class="dropdown-item">
                                    <div class="item-icon-bg"><i class="fas fa-sliders-h"></i></div>
                                    <span class="item-text">Society Dashboard</span>
                                </a>
                            <?php else: ?>
                                <!-- Student & Lecturer Menu Links -->
                                <a href="my_profile.php" class="dropdown-item">
                                    <div class="item-icon-bg"><i class="fas fa-user"></i></div>
                                    <span class="item-text">My Profile</span>
                                </a>
                                <a href="saved_events.php" class="dropdown-item">
                                    <div class="item-icon-bg"><i class="fas fa-bookmark"></i></div>
                                    <span class="item-text">Saved Events</span>
                                </a>
                            <?php endif; ?>

                            <div class="dropdown-divider desktop-dropdown-divider"></div>

                            <!-- ============================================================ -->
                            <!-- Settings & Privacy Dropdown (Desktop) - Modified             -->
                            <!-- ============================================================ -->
                            <div class="desktop-dropdown-links">
                                <!-- Change Password - Opens in new window -->
                                <a href="change_password.php" class="dropdown-item" target="_blank" rel="noopener noreferrer">
                                    <div class="item-icon-bg"><i class="fas fa-key"></i></div>
                                    <span class="item-text">Change Password</span>
                                    <i class="fas fa-external-link-alt" style="font-size: 0.7rem; margin-left: auto; color: #94a3b8;"></i>
                                </a>
                                <!-- Change Login Email - Opens in new window -->
                                <a href="change_email.php" class="dropdown-item" target="_blank" rel="noopener noreferrer">
                                    <div class="item-icon-bg"><i class="fas fa-envelope"></i></div>
                                    <span class="item-text">Change Login Email</span>
                                    <i class="fas fa-external-link-alt" style="font-size: 0.7rem; margin-left: auto; color: #94a3b8;"></i>
                                </a>
                                
                                <!-- Manage Account - Only show when active mode is society -->
                                <?php if ($active_mode === 'society'): ?>
                                    <a href="manage_account.php" class="dropdown-item">
                                        <div class="item-icon-bg"><i class="fas fa-user-cog"></i></div>
                                        <span class="item-text">Manage Account</span>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Log Out -->
                                <a href="logout.php" class="dropdown-item logout-item">
                                    <div class="item-icon-bg"><i class="fas fa-sign-out-alt"></i></div>
                                    <span class="item-text" style="color: #ef4444;">Log Out</span>
                                </a>
                            </div>

                            <!-- ============================================================ -->
                            <!-- Mobile drawer-style settings list - Modified                 -->
                            <!-- ============================================================ -->
                            <div class="mobile-dropdown-links">
                                <!-- "See more" button REMOVED -->
                                <!-- <button type="button" class="mobile-see-more-btn">
                                    <span>See more</span>
                                </button> -->

                                <!-- Settings and privacy - Modified to show separate items -->
                                <details class="mobile-accordion">
                                    <summary class="mobile-accordion-summary">
                                        <span class="mobile-accordion-title">
                                            <i class="fas fa-cog"></i>
                                            <span>Settings and privacy</span>
                                        </span>
                                        <i class="fas fa-chevron-down mobile-accordion-caret"></i>
                                    </summary>
                                    <div class="mobile-accordion-body">
                                        <!-- Change Password - Opens in new window -->
                                        <a href="change_password.php" class="mobile-accordion-item" target="_blank" rel="noopener noreferrer">
                                            <span class="mobile-accordion-item-icon"><i class="fas fa-key"></i></span>
                                            <span class="mobile-accordion-item-text">Change Password</span>
                                            <i class="fas fa-external-link-alt" style="font-size: 0.7rem; margin-left: auto; color: #94a3b8;"></i>
                                        </a>
                                        <!-- Change Login Email - Opens in new window -->
                                        <a href="change_email.php" class="mobile-accordion-item" target="_blank" rel="noopener noreferrer">
                                            <span class="mobile-accordion-item-icon"><i class="fas fa-envelope"></i></span>
                                            <span class="mobile-accordion-item-text">Change Login Email</span>
                                            <i class="fas fa-external-link-alt" style="font-size: 0.7rem; margin-left: auto; color: #94a3b8;"></i>
                                        </a>
                                        
                                        <!-- Manage Account - Only show when active mode is society -->
                                        <?php if ($active_mode === 'society'): ?>
                                            <a href="manage_account.php" class="mobile-accordion-item">
                                                <span class="mobile-accordion-item-icon"><i class="fas fa-user-cog"></i></span>
                                                <span class="mobile-accordion-item-text">Manage Account</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </details>

                                <a href="logout.php" class="mobile-logout-item">
                                    <span class="mobile-logout-icon"><i class="fas fa-sign-out-alt"></i></span>
                                    <span class="mobile-logout-text">Log Out</span>
                                </a>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- VIEW 2: Full profile list — personal account + all societies -->
                        <!-- ============================================================ -->
                        <div id="profileViewAll" style="display: none;">

                            <!-- Back button to return to the main dropdown view -->
                            <div style="display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer;" id="backToMainProfileBtn">
                                <div class="item-icon-bg" style="width: 36px; height: 36px; margin-right: 12px; background: rgba(255,255,255,0.1); color: #E4E6EB;"><i class="fas fa-arrow-left"></i></div>
                                <h3 style="margin: 0; font-size: 1.1rem; color: #E4E6EB;">Select profile</h3>
                            </div>

                            <div style="padding: 10px; max-height: 400px; overflow-y: auto;">

                                <!-- Personal user profile row — highlighted if currently active -->
                                <div class="fb-profile-row <?php echo ($active_mode === 'user') ? 'active-profile' : 'switch-profile-item quick-switch-btn'; ?>"
                                     data-type="user" data-id="0" data-status="verified"
                                     style="margin-bottom: 8px;">
                                    <div class="fb-avatar-circle"><?php echo $user_avatar_tag; ?></div>
                                    <span class="fb-profile-name" style="flex-grow: 1;"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?></span>
                                    <?php if ($active_mode === 'user'): ?>
                                        <i class="fas fa-check-circle fb-active-tick"></i>
                                    <?php else: ?>
                                        <div class="fb-switch-icon-wrapper"><i class="fas fa-sync-alt switchIcon"></i></div>
                                    <?php endif; ?>
                                </div>

                                <div class="dropdown-divider" style="margin: 10px 0;"></div>
                                <div style="font-size: 0.85rem; font-weight: 600; color: #b0b3b8; margin: 10px 5px; text-transform: uppercase;">Your Societies</div>

                                <!-- Iterate over all societies owned by the user -->
                                <?php foreach($all_societies as $soc): ?>
                                    <?php
                                    // Check if this society is the one currently active in the session
                                    $is_this_active = (
                                        $active_mode === 'society' &&
                                        isset($_SESSION['active_society_id']) &&
                                        $_SESSION['active_society_id'] == $soc['id']
                                    );
                                    $is_pending = ($soc['status'] === 'pending');
                                    $soc_avatar = $build_society_avatar($soc);
                                    ?>
                                    <div class="fb-profile-row <?php echo $is_this_active ? 'active-profile' : 'switch-profile-item quick-switch-btn'; ?>"
                                         data-type="society"
                                         data-id="<?php echo $soc['id']; ?>"
                                         data-status="<?php echo $soc['status']; ?>"
                                         style="margin-bottom: 5px;">
                                        <div class="fb-avatar-circle society-avatar <?php echo strpos($soc_avatar, '<img') !== false ? 'has-image' : ''; ?>"><?php echo $soc_avatar; ?></div>
                                        <span class="fb-profile-name" style="flex-grow: 1;">
                                            <?php echo htmlspecialchars($soc['society_name']); ?>
                                            <?php if($is_pending): ?>
                                                <span style="font-size: 0.65rem; background: #e41e3f; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">Pending</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($is_this_active): ?>
                                            <i class="fas fa-check-circle fb-active-tick"></i>
                                        <?php else: ?>
                                            <div class="fb-switch-icon-wrapper">
                                                <?php if($is_pending): ?>
                                                    <i class="fas fa-exclamation-circle" style="color: #e41e3f;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sync-alt switchIcon"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Option to register an additional society -->
                                <a href="create_society.php" style="text-decoration: none; display: block; margin-top: 10px;">
                                    <div class="fb-profile-row" style="background: rgba(255, 255, 255, 0.05);">
                                        <div class="fb-avatar-circle" style="background: rgba(255, 255, 255, 0.1); color: #E4E6EB;"><i class="fas fa-plus"></i></div>
                                        <span class="fb-profile-name" style="color: #E4E6EB; margin-left: 10px;">Create new society</span>
                                    </div>
                                </a>

                            </div>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <!-- Unauthenticated state — show login and register CTAs -->
                <a href="login.php"    class="btn btn-login">Login</a>
                <a href="register.php" class="btn btn-register">Register</a>
            <?php endif; ?>

            <!-- Hamburger button — visible on mobile viewports -->
            <div class="mobile-menu-btn"><i class="fas fa-bars"></i></div>
        </div>

    </div>
</header>

<script src="assets/js/header.js"></script>

<!-- Global header search script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

</body>
</html>