<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header-main.css">
    <link rel="stylesheet" href="assets/css/header-dropdown.css">
    <link rel="stylesheet" href="assets/css/header-responsive.css">

</head>
<body>

<header class="main-header">
    <div class="header-container">
        
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

        <nav class="header-center">
            <a href="index.php" class="nav-btn active" title="Home"><i class="fas fa-home"></i>  Home</a>
            <a href="events.php" class="nav-btn" title="Events"><i class="fas fa-calendar-alt"></i>  Events</a>
            <a href="notices.php" class="nav-btn" title="Notices"><i class="fas fa-thumbtack"></i>  Notices</a>
            <a href="societies.php" class="nav-btn" title="Societies"><i class="fas fa-users"></i>  Societies</a>
        </nav>

        <div class="header-right">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-register">Register</a>
    
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
        </div>
        
    </div> <div class="mobile-dropdown" id="mobileMenu">
        <div class="mobile-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search events...">
        </div>
        
        <nav class="mobile-nav">
            <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
            <a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a>
            <a href="notices.php"><i class="fas fa-thumbtack"></i> Notices</a>
            <a href="societies.php"><i class="fas fa-users"></i> Societies</a>
        </nav>
        
        <div class="mobile-auth">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-register">Register</a>
        </div>
    </div>
</header>

<script>
// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileDropdown = document.getElementById('mobileMenu');
    
    if (mobileMenuBtn && mobileDropdown) {
        // Toggle dropdown on menu button click
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking on a navigation link
        const mobileLinks = mobileDropdown.querySelectorAll('.mobile-nav a, .mobile-auth a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileDropdown.classList.remove('active');
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenuBtn.contains(e.target) && !mobileDropdown.contains(e.target)) {
                mobileDropdown.classList.remove('active');
            }
        });
        
        // Close dropdown on window resize (if going back to desktop view)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1060) {
                mobileDropdown.classList.remove('active');
            }
        });
    }
});
</script>

</body>
</html>