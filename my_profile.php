<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/function.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_data = [];

if (isset($pdo)) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$fullname = htmlspecialchars($user_data['fullname'] ?? 'User Name');
$email = htmlspecialchars($user_data['email'] ?? 'No Email');
$category = htmlspecialchars($user_data['category'] ?? 'student');

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

$saved_events_preview = [];
if (isset($pdo)) {
    $savedStmt = $pdo->prepare(
        "SELECT e.*, s.society_name,
            (SELECT COUNT(*) FROM event_likes WHERE event_id = e.id) AS likes_count,
            (SELECT COUNT(*) FROM saved_events WHERE event_id = e.id) AS saves_count,
            EXISTS(
                SELECT 1 FROM event_likes el
                WHERE el.event_id = e.id AND el.user_id = ?
            ) AS is_liked,
            EXISTS(
                SELECT 1 FROM saved_events se2
                WHERE se2.event_id = e.id AND se2.user_id = ?
            ) AS is_saved
        FROM saved_events se
        JOIN events e ON se.event_id = e.id
        JOIN societies s ON e.society_id = s.id
        WHERE se.user_id = ?
        ORDER BY se.created_at DESC
        LIMIT 6"
    );
    $savedStmt->execute([$user_id, $user_id, $user_id]);
    $saved_events_preview = $savedStmt->fetchAll(PDO::FETCH_ASSOC);
}

$followed_societies = [];
if (isset($pdo)) {
    $followedStmt = $pdo->prepare(
        "SELECT s.*,
            (SELECT COUNT(*) FROM events WHERE society_id = s.id) AS total_events,
            (SELECT COUNT(*) FROM events WHERE society_id = s.id AND status = 'upcoming') AS upcoming_events,
            (SELECT COUNT(*) FROM society_followers WHERE society_id = s.id) AS follower_count,
            (SELECT COUNT(*) FROM society_managers WHERE society_id = s.id) AS member_count
        FROM society_followers sf
        JOIN societies s ON sf.society_id = s.id
        WHERE sf.user_id = ? AND s.status = 'verified'
        ORDER BY sf.id DESC
        LIMIT 6"
    );
    $followedStmt->execute([$user_id]);
    $followed_societies = $followedStmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/event.css">
<link rel="stylesheet" href="assets/css/profile.css">

<div class="profile-container premium-profile">

    <div class="profile-header">
        <form action="update_photo.php" method="POST" enctype="multipart/form-data" id="coverForm" style="display: none;">
            <input type="hidden" name="photo_type" value="cover">
            <input type="file" id="coverUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('coverForm').submit();">
        </form>

        <?php if ($has_cover): ?>
            <div class="profile-cover" style="background-image: url('<?php echo $cover_url; ?>'); background-size: cover; background-position: center;">
        <?php else: ?>
            <div class="profile-cover profile-cover-placeholder">
                <i class="fas fa-image"></i>
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
                    <div class="profile-avatar-img profile-avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <button class="edit-avatar-btn" onclick="document.getElementById('avatarUpload').click();" aria-label="Edit profile photo"><i class="fas fa-camera"></i></button>
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
                    <span><i class="fas fa-university"></i> <?php echo $university; ?></span>
                    <?php if ($location !== 'Not specified'): ?>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $location; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-actions">
                <button class="btn-primary" onclick="openEditModal()"><i class="fas fa-pen-to-square"></i> Edit Profile</button>
            </div>
        </div>
    </div>

    <div class="profile-tabs-container">
        <button class="profile-tab active" onclick="openTab(event, 'about')">About</button>
        <button class="profile-tab" onclick="openTab(event, 'saved')">Saved Events</button>
        <button class="profile-tab" onclick="openTab(event, 'societies')">Followed Societies</button>
    </div>

    <div class="profile-content">

        <div id="about" class="tab-pane active">
            <div class="about-grid">
                <div class="info-card">
                    <h3><i class="fas fa-id-card"></i> Personal Information</h3>
                    <ul class="info-list">
                        <li><strong>Full Name</strong><span><?php echo $fullname; ?></span></li>
                        <li><strong>Email</strong><span><?php echo $email; ?></span></li>
                        <li><strong>Location</strong><span><?php echo $location; ?></span></li>
                        <li><strong>Account Type</strong><span class="highlight"><?php echo ucfirst($category); ?></span></li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Background</h3>
                    <ul class="info-list">
                        <?php if ($category === 'student'): ?>
                            <li><strong>Degree / Program</strong><span><?php echo !empty($qualifications) ? $qualifications : 'Not specified'; ?></span></li>
                            <li><strong>School</strong><span><?php echo $school; ?></span></li>
                            <li><strong>University</strong><span><?php echo $university; ?></span></li>
                        <?php else: ?>
                            <li><strong>Qualifications</strong><span><?php echo !empty($qualifications) ? $qualifications : 'Not specified'; ?></span></li>
                            <li><strong>Institution</strong><span><?php echo $university; ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div id="saved" class="tab-pane">
            <?php if (empty($saved_events_preview)): ?>
                <div class="events-empty profile-empty-state">
                    <i class="fas fa-bookmark"></i>
                    <h3>No saved events yet</h3>
                    <p>Bookmark events to keep them ready for quick access from your profile.</p>
                    <a href="events.php" class="action-btn-details">Explore Events</a>
                </div>
            <?php else: ?>
                <div class="section-intro">
                    <h3>Saved Events </h3>
                </div>
                <div class="events-grid profile-events-grid">
                    <?php foreach ($saved_events_preview as $event): ?>
                        <?php
                            $eventPoster = !empty($event['poster_path']) && file_exists('assets/images/events/' . $event['poster_path'])
                                ? 'assets/images/events/' . htmlspecialchars($event['poster_path'])
                                : '';
                            $statusValue = strtolower((string) ($event['status'] ?? 'upcoming'));
                            if (!in_array($statusValue, ['upcoming', 'ongoing', 'completed'], true)) {
                                $statusValue = 'upcoming';
                            }
                        ?>
                        <article class="event-card saved-event-card profile-event-card">
                            <div class="event-poster-wrap">
                                <?php if ($eventPoster): ?>
                                    <img src="<?= $eventPoster ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                                <?php else: ?>
                                    <div class="event-poster-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>No Flyer</span>
                                    </div>
                                <?php endif; ?>
                                <span class="event-status-badge badge-<?= htmlspecialchars($statusValue) ?>"><?= ucfirst($statusValue) ?></span>
                                <span class="event-category-chip"><?= htmlspecialchars($event['category'] ?? 'General') ?></span>
                            </div>

                            <div class="event-body">
                                <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-row">
                                        <i class="fas fa-users"></i>
                                        <a href="society_profile.php?id=<?= (int) $event['society_id'] ?>" class="society-link"><?= htmlspecialchars($event['society_name']) ?></a>
                                    </div>
                                    <div class="event-meta-row"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?> at <?= date('h:i A', strtotime($event['start_time'])) ?></div>
                                    <div class="event-meta-row"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue'] ?? 'Venue to be announced') ?></div>
                                </div>

                                <div class="event-footer">
                                    <button class="action-btn like-btn <?= !empty($event['is_liked']) ? 'liked' : '' ?>" data-id="<?= (int) $event['id'] ?>">
                                        <i class="fas fa-heart"></i> <span class="likes-count"><?= (int) $event['likes_count'] ?></span>
                                    </button>
                                    <button class="action-btn save-btn <?= !empty($event['is_saved']) ? 'saved' : '' ?>" data-id="<?= (int) $event['id'] ?>">
                                        <i class="fas fa-bookmark"></i> <span class="saves-count"><?= (int) $event['saves_count'] ?></span>
                                    </button>
                                    <a href="event_details.php?id=<?= (int) $event['id'] ?>" class="action-btn-details">View Details</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="section-cta-wrap">
                    <a href="saved_events.php" class="btn-primary section-cta"><i class="fas fa-arrow-right"></i> View All Saved Events</a>
                </div>
            <?php endif; ?>
        </div>

        <div id="societies" class="tab-pane">
            <?php if (empty($followed_societies)): ?>
                <div class="events-empty profile-empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No followed societies yet</h3>
                    <p>Follow societies to see their updates and activity highlights here.</p>
                    <a href="societies.php" class="action-btn-details">Explore Societies</a>
                </div>
            <?php else: ?>
                <div class="section-intro">
                    <h3>Communities You Follow</h3>
                </div>

                <div class="society-grid profile-society-grid">
                    <?php foreach ($followed_societies as $soc): ?>
                        <?php
                            $societyCover = !empty($soc['cover_path']) && file_exists('assets/images/uploads/' . $soc['cover_path'])
                                ? 'assets/images/uploads/' . htmlspecialchars($soc['cover_path'])
                                : '';

                            $societyLogo = !empty($soc['logo_path']) && file_exists('assets/images/uploads/' . $soc['logo_path'])
                                ? 'assets/images/uploads/' . htmlspecialchars($soc['logo_path'])
                                : '';

                            $societyAbout = trim((string) ($soc['description'] ?? ''));
                            $websiteUrl = trim((string) ($soc['website_url'] ?? ''));
                            $facebookUrl = trim((string) ($soc['facebook_url'] ?? ''));
                            $instagramUrl = trim((string) ($soc['instagram_link'] ?? ''));

                            $categoryLabel = trim((string) ($soc['faculty'] ?? ''));
                            if ($categoryLabel === '') {
                                $categoryLabel = 'General';
                            }
                        ?>
                        <article class="society-card" data-society-id="<?= (int) $soc['id'] ?>">
                            <a href="society_profile.php?id=<?= (int) $soc['id'] ?>" class="society-cover-wrap">
                                <?php if ($societyCover): ?>
                                    <img src="<?= $societyCover ?>" class="society-cover-image" alt="<?= htmlspecialchars($soc['society_name']) ?> cover">
                                <?php elseif ($societyLogo): ?>
                                    <img src="<?= $societyLogo ?>" class="society-cover-image" alt="<?= htmlspecialchars($soc['society_name']) ?> logo">
                                <?php else: ?>
                                    <div class="society-cover-placeholder"><i class="fas fa-users"></i></div>
                                <?php endif; ?>

                                <span class="society-category-chip"><?= htmlspecialchars($categoryLabel) ?></span>
                                <span class="society-verified-badge"><i class="fas fa-circle-check"></i> Verified</span>
                            </a>

                            <div class="society-card-body">
                                <div class="society-card-header">
                                    <div class="society-logo-wrap">
                                        <?php if ($societyLogo): ?>
                                            <img src="<?= $societyLogo ?>" class="society-logo-image" alt="<?= htmlspecialchars($soc['society_name']) ?>">
                                        <?php else: ?>
                                            <div class="society-logo-fallback"><i class="fas fa-university"></i></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="society-name-wrap">
                                        <h3 class="society-card-name">
                                            <a href="society_profile.php?id=<?= (int) $soc['id'] ?>"><?= htmlspecialchars($soc['society_name']) ?></a>
                                        </h3>
                                        <p class="society-founded"><i class="fas fa-calendar-alt"></i> Active community</p>
                                    </div>
                                </div>

                                <p class="society-about"><?= $societyAbout !== '' ? htmlspecialchars($societyAbout) : 'No society description added yet.' ?></p>

                                <div class="society-stats-grid compact-stats">
                                    <div class="society-stat-box">
                                        <i class="fas fa-users"></i>
                                        <span class="stat-label">Followers</span>
                                        <strong class="soc-followers-count"><?= (int) $soc['follower_count'] ?></strong>
                                    </div>
                                    <div class="society-stat-box">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span class="stat-label">Events</span>
                                        <strong><?= (int) $soc['total_events'] ?></strong>
                                    </div>
                                </div>

                                <div class="society-card-footer">
                                    <div class="society-social-links">
                                        <?php if ($websiteUrl !== ''): ?>
                                            <a href="<?= htmlspecialchars($websiteUrl) ?>" target="_blank" rel="noopener noreferrer" title="Website"><i class="fas fa-globe"></i></a>
                                        <?php endif; ?>
                                        <?php if ($facebookUrl !== ''): ?>
                                            <a href="<?= htmlspecialchars($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                                        <?php endif; ?>
                                        <?php if ($instagramUrl !== ''): ?>
                                            <a href="<?= htmlspecialchars($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" title="Instagram"><i class="fab fa-instagram"></i></a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="society-card-actions">
                                        <button
                                            type="button"
                                            class="soc-follow-btn is-following"
                                            data-id="<?= (int) $soc['id'] ?>"
                                        >
                                            <i class="fas fa-check"></i>
                                            <span class="follow-label">Following</span>
                                        </button>
                                        <a href="society_profile.php?id=<?= (int) $soc['id'] ?>" class="action-btn-details">View Profile <i class="fas fa-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="section-cta-wrap">
                    <a href="societies.php" class="btn-primary section-cta"><i class="fas fa-arrow-right"></i> View More Societies</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<div id="editProfileModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            <button class="close-modal-btn" onclick="closeEditModal()" aria-label="Close modal">&times;</button>
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

            <?php if ($category === 'student'): ?>
                <div class="form-group">
                    <label>School</label>
                    <input type="text" name="school" value="<?php echo $school !== 'Not specified' ? $school : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Degree / Program</label>
                    <input type="text" name="qualifications" value="<?php echo $qualifications; ?>" placeholder="e.g. BSc in Computer Science">
                </div>
            <?php else: ?>
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
function openTab(evt, tabName) {
    const tabContent = document.getElementsByClassName('tab-pane');
    const tabLinks = document.getElementsByClassName('profile-tab');

    for (let i = 0; i < tabContent.length; i++) {
        tabContent[i].style.display = 'none';
        tabContent[i].classList.remove('active');
    }

    for (let i = 0; i < tabLinks.length; i++) {
        tabLinks[i].classList.remove('active');
    }

    const nextPane = document.getElementById(tabName);
    if (nextPane) {
        nextPane.style.display = 'block';
        nextPane.classList.add('active');
    }

    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    }
}

function openEditModal() {
    const modal = document.getElementById('editProfileModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }
}

function closeEditModal() {
    const modal = document.getElementById('editProfileModal');
    if (modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }
}

window.addEventListener('click', function (event) {
    const modal = document.getElementById('editProfileModal');
    if (event.target === modal) {
        closeEditModal();
    }
});
</script>

<script src="assets/js/event.js"></script>
<script src="assets/js/societies.js"></script>
