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
                <input type="text" placeholder="Search events...">
            </div>
        </div>

        <!-- Centre navigation: primary site links -->
        <nav class="header-center">
            <a href="index.php"      class="nav-btn active" title="Home">     <i class="fas fa-home"></i>        Home</a>
            <a href="events.php"     class="nav-btn"        title="Events">    <i class="fas fa-calendar-alt"></i> Events</a>
            <a href="notices.php"    class="nav-btn"        title="Notices">   <i class="fas fa-thumbtack"></i>    Notices</a>
            <a href="societies.php"  class="nav-btn"        title="Societies"> <i class="fas fa-users"></i>        Societies</a>
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
                    // Render a society icon if currently in society mode, otherwise render a user icon
                    $top_avatar = ($active_mode === 'society')
                        ? '<i class="fas fa-users"></i>'
                        : '<i class="fas fa-user"></i>';
                    ?>

                    <!-- Profile dropdown trigger button -->
                    <button class="profile-btn" id="profileDropdownBtn">
                        <div class="profile-avatar"><?php echo $top_avatar; ?></div>
                        <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown" style="width: 340px;">

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

                        // Resolve the display name and icon for the currently active profile
                        $active_name = $_SESSION['fullname'];
                        $active_icon = '<i class="fas fa-user"></i>';
                        if ($active_mode === 'society' && isset($_SESSION['active_society_name'])) {
                            $active_name = $_SESSION['active_society_name'];
                            $active_icon = '<i class="fas fa-users"></i>';
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
                                        <div class="fb-avatar-circle"><i class="fas fa-user"></i></div>
                                        <span class="fb-profile-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                                        <div class="fb-switch-icon-wrapper"><i class="fas fa-sync-alt switchIcon"></i></div>
                                    </div>

                                <?php else: ?>
                                    <?php if($has_society): ?>
                                        <?php
                                        // Display the first society as a quick-switch option
                                        $first_soc        = $all_societies[0];
                                        $is_first_pending = ($first_soc['status'] === 'pending');
                                        ?>
                                        <div class="fb-profile-row switch-profile-item quick-switch-btn"
                                             data-type="society"
                                             data-id="<?php echo $first_soc['id']; ?>"
                                             data-status="<?php echo $first_soc['status']; ?>">
                                            <div class="fb-avatar-circle society-avatar"><i class="fas fa-users"></i></div>
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

                            <div class="dropdown-divider"></div>

                            <!-- ============================================================ -->
                            <!-- Common Settings (Visible to everyone)                        -->
                            <!-- ============================================================ -->
                            <a href="settings.php" class="dropdown-item">
                                <div class="item-icon-bg"><i class="fas fa-cog"></i></div>
                                <span class="item-text">Settings & privacy</span>
                            </a>
                            <a href="help.php" class="dropdown-item">
                                <div class="item-icon-bg"><i class="fas fa-question-circle"></i></div>
                                <span class="item-text">Help & Support</span>
                            </a>
                            <a href="logout.php" class="dropdown-item logout-item">
                                <div class="item-icon-bg"><i class="fas fa-sign-out-alt"></i></div>
                                <span class="item-text" style="color: #ef4444;">Log Out</span>
                            </a>
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
                                    <div class="fb-avatar-circle"><i class="fas fa-user"></i></div>
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
                                    ?>
                                    <div class="fb-profile-row <?php echo $is_this_active ? 'active-profile' : 'switch-profile-item quick-switch-btn'; ?>"
                                         data-type="society"
                                         data-id="<?php echo $soc['id']; ?>"
                                         data-status="<?php echo $soc['status']; ?>"
                                         style="margin-bottom: 5px;">
                                        <div class="fb-avatar-circle society-avatar"><i class="fas fa-users"></i></div>
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

</body>
</html>