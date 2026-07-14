<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'config/app.php';
require_once 'includes/function.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$view_user_id = (int) ($_GET['id'] ?? 0);
$session_user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($view_user_id <= 0) {
    header('Location: events.php');
    exit();
}

if ($view_user_id === $session_user_id) {
    header('Location: my_profile.php');
    exit();
}

$stmt = $pdo->prepare('SELECT id, fullname, category, avatar_url, cover_url, tagline, location, university, school, qualifications, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$view_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: events.php');
    exit();
}

$fullname = htmlspecialchars((string) ($user['fullname'] ?? 'Campus Member'));
$category = strtolower(trim((string) ($user['category'] ?? 'student')));
$taglineRaw = trim((string) ($user['tagline'] ?? ''));
$tagline = htmlspecialchars($taglineRaw !== '' ? $taglineRaw : 'CampusHub community member');
$locationRaw = trim((string) ($user['location'] ?? ''));
$location = htmlspecialchars($locationRaw !== '' ? $locationRaw : 'Not shared');
$universityRaw = trim((string) ($user['university'] ?? ''));
$university = htmlspecialchars($universityRaw !== '' ? $universityRaw : 'Not shared');
$schoolRaw = trim((string) ($user['school'] ?? ''));
$school = htmlspecialchars($schoolRaw !== '' ? $schoolRaw : 'Not shared');
$qualificationsRaw = trim((string) ($user['qualifications'] ?? ''));
$qualifications = htmlspecialchars($qualificationsRaw !== '' ? $qualificationsRaw : 'Not shared');

$createdAt = trim((string) ($user['created_at'] ?? ''));
$memberSince = $createdAt !== '' ? date('F Y', strtotime($createdAt)) : 'Unknown';

$db_avatar = (string) ($user['avatar_url'] ?? '');
$db_cover = (string) ($user['cover_url'] ?? '');
$has_avatar = $db_avatar !== '' && $db_avatar !== 'assets/images/default_avatar.png';
$has_cover = $db_cover !== '' && $db_cover !== 'assets/images/default_cover.png';
$avatar_url = $has_avatar ? app_url($db_avatar) : '';
$cover_url = $has_cover ? app_url($db_cover) : '';

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/event.css">
<link rel="stylesheet" href="assets/css/profile.css">

<div class="profile-container premium-profile public-profile">
    <div class="profile-header">
        <?php if ($has_cover): ?>
            <div class="profile-cover" style="background-image: url('<?php echo $cover_url; ?>'); background-size: cover; background-position: center;"></div>
        <?php else: ?>
            <div class="profile-cover profile-cover-placeholder"><i class="fas fa-image"></i></div>
        <?php endif; ?>

        <div class="profile-info-section public-info-section">
            <div class="profile-avatar-wrapper">
                <?php if ($has_avatar): ?>
                    <img src="<?php echo $avatar_url; ?>" alt="Profile" class="profile-avatar-img">
                <?php else: ?>
                    <div class="profile-avatar-img profile-avatar-placeholder"><i class="fas fa-user"></i></div>
                <?php endif; ?>
            </div>

            <div class="profile-details">
                <h1 class="profile-name">
                    <?php echo $fullname; ?>
                    <?php if ($category === 'lecturer'): ?>
                        <i class="fas fa-check-circle lecturer-badge" title="Lecturer"></i>
                    <?php endif; ?>
                </h1>
                <p class="profile-tagline"><?php echo $tagline; ?></p>
                <div class="profile-meta-row">
                    <span><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars(ucfirst($category)); ?></span>
                    <span><i class="fas fa-university"></i> <?php echo $university; ?></span>
                    <span><i class="fas fa-clock"></i> Member since <?php echo htmlspecialchars($memberSince); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-content public-content">
        <div class="about-grid public-about-grid">
            <div class="info-card">
                <h3><i class="fas fa-id-card"></i> Public Profile</h3>
                <ul class="info-list">
                    <li><strong>Location</strong><span><?php echo $location; ?></span></li>
                    <li><strong>Institution</strong><span><?php echo $university; ?></span></li>
                    <li><strong>School</strong><span><?php echo $school; ?></span></li>
                    <li><strong>Background</strong><span><?php echo $qualifications; ?></span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
