<?php
// society_dashboard.php
// Management view for a society's own admin (the user who created/owns it).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/function.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$society_id = (int) ($_GET['id'] ?? ($_SESSION['active_society_id'] ?? 0));

$stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ?");
$stmt->execute([$society_id]);
$society = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$society) {
    header("Location: societies.php");
    exit();
}

// Only the owning admin may view/manage this dashboard
if ((int) $society['admin_id'] !== (int) getUserId()) {
    header("Location: society_profile.php?id=" . $society_id);
    exit();
}

$name        = htmlspecialchars($society['society_name']);
$faculty     = htmlspecialchars($society['faculty'] ?? '');
$description = htmlspecialchars($society['description'] ?? 'No description added yet.');
$vision      = htmlspecialchars($society['vision'] ?? '');
$mission     = htmlspecialchars($society['mission'] ?? '');
$address     = htmlspecialchars($society['address'] ?? '');
$email1      = htmlspecialchars($society['email_1'] ?? '');
$email2      = htmlspecialchars($society['email_2'] ?? '');
$contact     = htmlspecialchars($society['contact_number'] ?? '');
$website     = htmlspecialchars($society['website_url'] ?? '');
$facebook    = htmlspecialchars($society['facebook_url'] ?? '');
$instagram   = htmlspecialchars($society['instagram_link'] ?? '');
$founded     = !empty($society['founded_date']) ? date('F Y', strtotime($society['founded_date'])) : 'Not specified';

$has_logo  = !empty($society['logo_path']) && file_exists('assets/images/uploads/' . $society['logo_path']);
$has_cover = !empty($society['cover_path']) && file_exists('assets/images/uploads/' . $society['cover_path']);
$logo_url  = $has_logo ? 'assets/images/uploads/' . htmlspecialchars($society['logo_path']) : '';
$cover_url = $has_cover ? 'assets/images/uploads/' . htmlspecialchars($society['cover_path']) : '';

// Events created by this society
$eventStmt = $pdo->prepare("SELECT * FROM events WHERE society_id = ? ORDER BY event_date DESC");
$eventStmt->execute([$society_id]);
$events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

// Followers
$followerStmt = $pdo->prepare("
    SELECT u.id, u.fullname, u.avatar_url
    FROM society_followers sf
    JOIN users u ON u.id = sf.user_id
    WHERE sf.society_id = ?
    ORDER BY sf.created_at DESC
");
$followerStmt->execute([$society_id]);
$followers = $followerStmt->fetchAll(PDO::FETCH_ASSOC);
$followerCount = count($followers);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> - Manage Society | CampusHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/society.css">
</head>
<body>

<div class="soc-container">

    <div class="soc-header">
        <form action="update_society_photo.php" method="POST" enctype="multipart/form-data" id="coverForm" style="display:none;">
            <input type="hidden" name="society_id" value="<?= $society_id ?>">
            <input type="hidden" name="photo_type" value="cover">
            <input type="file" id="coverUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('coverForm').submit();">
        </form>

        <div class="soc-cover" <?= $has_cover ? "style=\"background-image:url('$cover_url')\"" : '' ?>>
            <?php if (!$has_cover): ?><i class="fas fa-image"></i><?php endif; ?>
            <button class="edit-cover-btn" onclick="document.getElementById('coverUpload').click();"><i class="fas fa-camera"></i> Edit Cover</button>
        </div>

        <div class="soc-info-section">
            <form action="update_society_photo.php" method="POST" enctype="multipart/form-data" id="logoForm" style="display:none;">
                <input type="hidden" name="society_id" value="<?= $society_id ?>">
                <input type="hidden" name="photo_type" value="avatar">
                <input type="file" id="logoUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('logoForm').submit();">
            </form>

            <div class="soc-logo-wrapper">
                <?php if ($has_logo): ?>
                    <img src="<?= $logo_url ?>" alt="<?= $name ?>" class="soc-logo-img">
                <?php else: ?>
                    <div class="soc-logo-img"><i class="fas fa-landmark"></i></div>
                <?php endif; ?>
                <button class="edit-logo-btn" onclick="document.getElementById('logoUpload').click();"><i class="fas fa-camera"></i></button>
            </div>

            <div class="soc-details">
                <h1 class="soc-name">
                    <?= $name ?>
                    <?php if ($society['status'] === 'verified'): ?>
                        <i class="fas fa-check-circle soc-verified-badge" title="Verified Society"></i>
                    <?php endif; ?>
                </h1>
                <p class="soc-tagline"><?= $faculty ? $faculty : 'Welcome to our official page!' ?></p>
                <div class="soc-meta">
                    <span><i class="fas fa-users"></i> <?= $followerCount ?> follower<?= $followerCount === 1 ? '' : 's' ?></span>
                    <span><i class="fas fa-calendar"></i> <?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?></span>
                    <span><i class="fas fa-shield-alt"></i> <?= ucfirst($society['status']) ?></span>
                </div>
            </div>

            <div class="soc-actions">
                <a href="create_event.php?society_id=<?= $society_id ?>" class="btn-primary"><i class="fas fa-plus"></i> Create Event</a>
                <button class="btn-secondary" onclick="openEditModal()"><i class="fas fa-cog"></i> Manage</button>
            </div>
        </div>
    </div>

    <div class="soc-tabs-container">
        <button class="soc-tab active" onclick="openSocTab(event, 'about')">About</button>
        <button class="soc-tab" onclick="openSocTab(event, 'photos')">Photos</button>
        <button class="soc-tab" onclick="openSocTab(event, 'events')">Events</button>
        <button class="soc-tab" onclick="openSocTab(event, 'followers')">Followers</button>
    </div>

    <div class="soc-content">

        <div id="about" class="tab-pane active">
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> About</h3>
                <p style="color:#cfd2d6; line-height:1.6;"><?= nl2br($description) ?></p>
            </div>

            <?php if ($vision || $mission): ?>
            <div class="info-card">
                <h3><i class="fas fa-bullseye"></i> Vision &amp; Mission</h3>
                <ul class="info-list">
                    <?php if ($vision): ?><li><strong>Vision:</strong> <?= nl2br($vision) ?></li><?php endif; ?>
                    <?php if ($mission): ?><li><strong>Mission:</strong> <?= nl2br($mission) ?></li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="info-card">
                <h3><i class="fas fa-address-card"></i> Contact &amp; Details</h3>
                <ul class="info-list">
                    <li><strong>Faculty:</strong> <?= $faculty ?: 'Not specified' ?></li>
                    <li><strong>Founded:</strong> <?= $founded ?></li>
                    <li><strong>Address:</strong> <?= $address ?: 'Not specified' ?></li>
                    <li><strong>Primary Email:</strong> <?= $email1 ?></li>
                    <li><strong>Secondary Email:</strong> <?= $email2 ?></li>
                    <?php if ($contact): ?><li><strong>Contact No:</strong> <?= $contact ?></li><?php endif; ?>
                    <?php if ($website): ?><li><strong>Website:</strong> <a href="<?= $website ?>" target="_blank" style="color:#F97316;"><?= $website ?></a></li><?php endif; ?>
                    <?php if ($facebook): ?><li><strong>Facebook:</strong> <a href="<?= $facebook ?>" target="_blank" style="color:#F97316;">View page</a></li><?php endif; ?>
                    <?php if ($instagram): ?><li><strong>Instagram:</strong> <a href="<?= $instagram ?>" target="_blank" style="color:#F97316;">View page</a></li><?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="photos" class="tab-pane">
            <?php if ($has_logo || $has_cover): ?>
                <div class="soc-photos-grid">
                    <?php if ($has_cover): ?><img src="<?= $cover_url ?>" alt="Cover"><?php endif; ?>
                    <?php if ($has_logo): ?><img src="<?= $logo_url ?>" alt="Logo"><?php endif; ?>
                </div>
            <?php else: ?>
                <div class="soc-empty-state">
                    <i class="fas fa-images"></i>
                    <h3>No photos yet</h3>
                    <p>Upload a cover photo and logo to showcase your society.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="events" class="tab-pane">
            <?php if (!empty($events)): ?>
                <div class="soc-events-grid">
                    <?php foreach ($events as $ev): ?>
                        <?php
                            $poster = !empty($ev['poster_path']) && file_exists($ev['poster_path']) ? $ev['poster_path'] : '';
                        ?>
                        <a href="event_details.php?id=<?= $ev['id'] ?>" class="soc-event-card" style="text-decoration:none;">
                            <?php if ($poster): ?>
                                <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($ev['title']) ?>">
                            <?php endif; ?>
                            <div class="soc-event-card-body">
                                <h4><?= htmlspecialchars($ev['title']) ?></h4>
                                <p><i class="fas fa-calendar-day"></i> <?= date('M d, Y', strtotime($ev['event_date'])) ?></p>
                                <?php if (!empty($ev['venue'])): ?><p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ev['venue']) ?></p><?php endif; ?>
                                <span class="soc-status-pill"><?= htmlspecialchars($ev['status']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="soc-empty-state">
                    <i class="fas fa-calendar-xmark"></i>
                    <h3>No events yet</h3>
                    <p>Events you create will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="followers" class="tab-pane">
            <?php if (!empty($followers)): ?>
                <div class="soc-followers-grid">
                    <?php foreach ($followers as $f): ?>
                        <?php
                            $favatar = !empty($f['avatar_url']) && $f['avatar_url'] !== 'assets/images/default_avatar.png'
                                ? $f['avatar_url'] : '';
                        ?>
                        <div class="soc-follower-card">
                            <?php if ($favatar): ?>
                                <img src="<?= htmlspecialchars($favatar) ?>" class="soc-follower-avatar" alt="">
                            <?php else: ?>
                                <div class="soc-follower-avatar" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <p><?= htmlspecialchars($f['fullname']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="soc-empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No followers yet</h3>
                    <p>Followers will show up here once students follow your society.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function openSocTab(evt, tabName) {
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.soc-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}
function openEditModal() {
    window.location.href = 'edit_society.php?id=<?= $society_id ?>';
}
</script>

</body>
</html>
