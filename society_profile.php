<?php
// society_profile.php
// Public-facing view of a society's profile, as seen by any logged-in student/lecturer.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/function.php';

$society_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ? AND status = 'verified'");
$stmt->execute([$society_id]);
$society = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$society) {
    header("Location: societies.php");
    exit();
}

// "Manage Society" only appears when this is the society the user is CURRENTLY
// acting as (active_mode = 'society' + matching active_society_id), set via
// switch_role.php. A user can administer several societies, but only the one
// they've actively switched into should show as theirs to manage here — every
// other society (even ones they also own) is viewed as a normal visitor.
$isOwner = isLoggedIn()
    && (($_SESSION['active_mode'] ?? 'user') === 'society')
    && (int) ($_SESSION['active_society_id'] ?? 0) === $society_id;

$name        = htmlspecialchars($society['society_name']);
$faculty     = htmlspecialchars($society['faculty'] ?? '');
$description = htmlspecialchars($society['description'] ?? 'No description added yet.');
$vision      = htmlspecialchars($society['vision'] ?? '');
$mission     = htmlspecialchars($society['mission'] ?? '');
$address     = htmlspecialchars($society['address'] ?? '');
$email1      = htmlspecialchars($society['email_1'] ?? '');
$contact     = htmlspecialchars($society['contact_number'] ?? '');
$website     = htmlspecialchars($society['website_url'] ?? '');
$facebook    = htmlspecialchars($society['facebook_url'] ?? '');
$instagram   = htmlspecialchars($society['instagram_link'] ?? '');
$foundedSource = $society['founded_date'] ?? '';
if (empty($foundedSource) && !empty($society['created_at'])) {
    $foundedSource = date('Y-m-d', strtotime($society['created_at']));
}
$founded     = !empty($foundedSource) ? date('F Y', strtotime($foundedSource)) : 'Not specified';

$has_logo  = !empty($society['logo_path']) && file_exists('assets/images/uploads/' . $society['logo_path']);
$has_cover = !empty($society['cover_path']) && file_exists('assets/images/uploads/' . $society['cover_path']);
$logo_url  = $has_logo ? 'assets/images/uploads/' . htmlspecialchars($society['logo_path']) : '';
$cover_url = $has_cover ? 'assets/images/uploads/' . htmlspecialchars($society['cover_path']) : '';

$eventStmt = $pdo->prepare("SELECT * FROM events WHERE society_id = ? ORDER BY event_date DESC");
$eventStmt->execute([$society_id]);
$events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

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

$isFollowing = false;
if (isLoggedIn()) {
    $checkStmt = $pdo->prepare("SELECT id FROM society_followers WHERE society_id = ? AND user_id = ?");
    $checkStmt->execute([$society_id, getUserId()]);
    $isFollowing = (bool) $checkStmt->fetchColumn();
}

$adminEmail = '';
$adminEmailStmt = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
$adminEmailStmt->execute([(int) $society['admin_id']]);
$adminEmailRaw = trim((string) $adminEmailStmt->fetchColumn());
if ($adminEmailRaw !== '') {
    $adminEmail = htmlspecialchars($adminEmailRaw);
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> - CampusHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/society.css">
</head>
<body>

<div class="soc-container">

    <div class="soc-header">
        <div class="soc-cover" <?= $has_cover ? "style=\"background-image:url('$cover_url')\"" : '' ?>>
            <?php if (!$has_cover): ?><i class="fas fa-image"></i><?php endif; ?>
        </div>

        <div class="soc-info-section">
            <div class="soc-logo-wrapper">
                <?php if ($has_logo): ?>
                    <img src="<?= $logo_url ?>" alt="<?= $name ?>" class="soc-logo-img">
                <?php else: ?>
                    <div class="soc-logo-img"><i class="fas fa-landmark"></i></div>
                <?php endif; ?>
            </div>

            <div class="soc-details">
                <h1 class="soc-name">
                    <?= $name ?>
                    <i class="fas fa-check-circle soc-verified-badge" title="Verified Society"></i>
                </h1>
                <p class="soc-tagline"><?= $faculty ? $faculty : 'Official Society Page' ?></p>
                <div class="soc-meta">
                    <span id="followerCountLabel"><i class="fas fa-users"></i> <?= $followerCount ?> follower<?= $followerCount === 1 ? '' : 's' ?></span>
                    <span><i class="fas fa-calendar"></i> <?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?></span>
                </div>
            </div>

            <div class="soc-actions">
                <?php if ($isOwner): ?>
                    <a href="society_dashboard.php?id=<?= $society_id ?>" class="btn-primary"><i class="fas fa-cog"></i> Manage Society</a>
                <?php elseif (isLoggedIn()): ?>
                    <button id="followBtn" class="btn-primary btn-follow <?= $isFollowing ? 'is-following' : '' ?>"
                            data-society-id="<?= $society_id ?>" onclick="toggleFollow(this)">
                        <i class="fas <?= $isFollowing ? 'fa-check' : 'fa-plus' ?>"></i>
                        <span class="follow-text"><?= $isFollowing ? 'Following' : 'Follow' ?></span>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="btn-primary"><i class="fas fa-sign-in-alt"></i> Log in to Follow</a>
                <?php endif; ?>
                <a href="events.php?society_id=<?= $society_id ?>" class="btn-secondary"><i class="fas fa-calendar"></i> View Events</a>
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
                    <?php if ($adminEmail): ?><li><strong>Admin Email:</strong> <?= $adminEmail ?></li><?php endif; ?>
                    <li><strong>Email:</strong> <?= $email1 ?></li>
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
                    <p>Be the first to follow this society!</p>
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

function toggleFollow(btn) {
    const societyId = btn.dataset.societyId;
    btn.disabled = true;
    fetch('follow_society.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'society_id=' + encodeURIComponent(societyId)
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (!data.success) {
            alert(data.message || 'Something went wrong.');
            return;
        }
        const icon = btn.querySelector('i');
        const label = btn.querySelector('.follow-text');
        if (data.following) {
            btn.classList.add('is-following');
            icon.className = 'fas fa-check';
            label.textContent = 'Following';
        } else {
            btn.classList.remove('is-following');
            icon.className = 'fas fa-plus';
            label.textContent = 'Follow';
        }
        document.getElementById('followerCountLabel').innerHTML =
            '<i class="fas fa-users"></i> ' + data.followers + ' follower' + (data.followers === 1 ? '' : 's');
    })
    .catch(() => {
        btn.disabled = false;
        alert('Network error. Please try again.');
    });
}
</script>

</body>
</html>
