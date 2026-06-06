<?php
// my_profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the database connection. $pdo object becomes available after this.
require_once 'config/database.php'; 

// If the user is not logged in, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];

// Fetch the user's data from the database to display on the profile page.
if (isset($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Use null coalescing operator to provide default values if any field is missing in the database.
$fullname = htmlspecialchars($user_data['fullname'] ?? 'User Name');
$email = htmlspecialchars($user_data['email'] ?? 'No Email');
$category = htmlspecialchars($user_data['category'] ?? 'student'); // student or lecturer

$tagline = htmlspecialchars($user_data['tagline'] ?? 'Welcome to CampusHub!');
$location = htmlspecialchars($user_data['location'] ?? 'Not specified');
$university = htmlspecialchars($user_data['university'] ?? 'Not specified');
$school = htmlspecialchars($user_data['school'] ?? 'Not specified');
$qualifications = htmlspecialchars($user_data['qualifications'] ?? '');

$db_avatar = $user_data['avatar_url'] ?? '';
$db_cover = $user_data['cover_url'] ?? '';

$has_avatar = !empty($db_avatar) && $db_avatar !== 'assets/images/default_avatar.png';
$has_cover = !empty($db_cover) && $db_cover !== 'assets/images/default_cover.png';

$avatar_url = $has_avatar ? '/campushub/' . htmlspecialchars($db_avatar) : '';
$cover_url = $has_cover ? '/campushub/' . htmlspecialchars($db_cover) : '';

// includes the header bar
include 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CampusHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>

<div class="profile-container">
    
    <div class="profile-header">
        <form action="update_photo.php" method="POST" enctype="multipart/form-data" id="coverForm" style="display: none;">
            <input type="hidden" name="photo_type" value="cover">
            <input type="file" id="coverUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('coverForm').submit();">
        </form>

        <?php if ($has_cover): ?>
            <div class="profile-cover" style="background-image: url('<?php echo $cover_url; ?>'); background-size: cover; background-position: center;">
        <?php else: ?>
            <div class="profile-cover" style="background-color: #3a3b3c; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-image" style="font-size: 3rem; color: #b0b3b8; opacity: 0.5;"></i>
        <?php endif; ?>
            <button class="edit-cover-btn" onclick="document.getElementById('coverUpload').click();"><i class="fas fa-camera"></i> Edit Cover</button>
        </div>
        
        <div class="profile-info-section">
            <form action="update_photo.php" method="POST" enctype="multipart/form-data" id="avatarForm" style="display: none;">
                <input type="hidden" name="photo_type" value="avatar">
                <input type="file" id="avatarUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('avatarForm').submit();">
            </form>

            <div class="profile-avatar-wrapper">
                <?php if ($has_avatar): ?>
                    <img src="<?php echo $avatar_url; ?>" alt="Profile" class="profile-avatar-img">
                <?php else: ?>
                    <div class="profile-avatar-img" style="display: flex; align-items: center; justify-content: center; background: #242526; font-size: 4rem; color: #b0b3b8;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <button class="edit-avatar-btn" onclick="document.getElementById('avatarUpload').click();"><i class="fas fa-camera"></i></button>
            </div>
            
            <div class="profile-details">
                <h1 class="profile-name">
                    <?php echo $fullname; ?> 
                    <?php if($category === 'lecturer'): ?>
                        <i class="fas fa-check-circle" style="color: #3b82f6; font-size: 1.1rem; margin-left: 5px;" title="Lecturer"></i>
                    <?php endif; ?>
                </h1>
                <p class="profile-tagline"><?php echo $tagline; ?></p>
                <p class="profile-university">
                    <i class="fas fa-university"></i> <?php echo $university; ?>
                    <?php if($location !== 'Not specified'): ?>
                        <span style="color: #b0b3b8; margin-left: 10px;"><i class="fas fa-map-marker-alt"></i> <?php echo $location; ?></span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="profile-actions">
                <button class="btn-primary" onclick="openEditModal()"><i class="fas fa-edit"></i> Edit Profile</button>
            </div>
        </div>
    </div>

    <div class="profile-tabs-container">
        <button class="profile-tab active" onclick="openTab(event, 'about')">About</button>
        <button class="profile-tab" onclick="openTab(event, 'societies')">Societies</button>
    </div>

    <div class="profile-content">
        
        <div id="about" class="tab-pane active">
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <ul class="info-list">
                    <li><strong>Full Name:</strong> <?php echo $fullname; ?></li>
                    <li><strong>Email:</strong> <?php echo $email; ?></li>
                    <li><strong>Location:</strong> <?php echo $location; ?></li>
                    <li><strong>Account Type:</strong> <span style="text-transform: capitalize; color: #F97316; font-weight: 600;"><?php echo $category; ?></span></li>
                </ul>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-graduation-cap"></i> Academic Background</h3>
                <ul class="info-list">
                    <?php if ($category === 'student'): ?>
                        <li><strong>University:</strong> <?php echo $university; ?></li>
                        <li><strong>School:</strong> <?php echo $school; ?></li>
                        <?php if (!empty($qualifications)): ?>
                            <li><strong>Degree:</strong> <?php echo $qualifications; ?></li>
                        <?php endif; ?>
                    <?php elseif ($category === 'lecturer'): ?>
                        <li><strong>Institution:</strong> <?php echo $university; ?></li>
                        <li><strong>Qualifications:</strong> <?php echo !empty($qualifications) ? $qualifications : 'Not specified'; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="societies" class="tab-pane">
            <div class="society-grid">
                <div class="society-card">
                    <div class="soc-icon"><i class="fas fa-laptop-code"></i></div>
                    <h4>Computer Science Society</h4>
                    <p>Member</p>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="editProfileModal" class="modal-overlay">
    <div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            <button class="close-modal-btn" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form action="update_profile.php" method="POST" class="edit-profile-form">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?php echo $fullname; ?>" required>
            </div>

            <div class="form-group">
                <label>Tagline / Bio</label>
                <input type="text" name="tagline" value="<?php echo $tagline !== 'Welcome to CampusHub!' ? $tagline : ''; ?>" placeholder="e.g. Tech enthusiast | Undergrad">
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?php echo $location !== 'Not specified' ? $location : ''; ?>" placeholder="e.g. Colombo, Sri Lanka">
            </div>

            <div class="form-group">
                <label>University / Institution</label>
                <input type="text" name="university" value="<?php echo $university !== 'Not specified' ? $university : ''; ?>">
            </div>

            <?php if ($category === 'student'): ?>
                <div class="form-group">
                    <label>School</label>
                    <input type="text" name="school" value="<?php echo $school !== 'Not specified' ? $school : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Degree / Program</label>
                    <input type="text" name="qualifications" value="<?php echo $qualifications; ?>" placeholder="e.g. BSc in Computer Science">
                </div>
            <?php elseif ($category === 'lecturer'): ?>
                <div class="form-group">
                    <label>Qualifications</label>
                    <input type="text" name="qualifications" value="<?php echo $qualifications; ?>" placeholder="e.g. PhD, MSc in Software Engineering">
                </div>
            <?php endif; ?>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Tab Switching Logic
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-pane");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("profile-tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.className += " active";
}

// Modal Open/Close Logic
function openEditModal() {
    document.getElementById('editProfileModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editProfileModal').style.display = 'none';
}

window.onclick = function(event) {
    let modal = document.getElementById('editProfileModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>