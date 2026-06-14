<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$event_id = $_GET['id'] ?? 0;
if (!$event_id) {
    header("Location: events.php");
    exit;
}

$stmt = $pdo->prepare("SELECT e.*, s.society_name FROM events e JOIN societies s ON e.society_id = s.id WHERE e.id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='container'>Event not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

$liked = isLoggedIn() && userLikedEvent(getUserId(), $event_id, $pdo);
$saved = isLoggedIn() && userSavedEvent(getUserId(), $event_id, $pdo);
$eventPoster = !empty($event['poster_path']) && file_exists('assets/images/events/' . $event['poster_path'])
    ? 'assets/images/events/' . htmlspecialchars($event['poster_path'])
    : '';


$like_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_likes WHERE event_id = ?");
$like_count_stmt->execute([$event_id]);
$like_count = (int) $like_count_stmt->fetchColumn();

$save_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_events WHERE event_id = ?");
$save_count_stmt->execute([$event_id]);
$save_count = (int) $save_count_stmt->fetchColumn();
?>

<link rel="stylesheet" href="assets/css/event.css">

<div class="container event-detail-shell">
    <div class="event-detail-card">
        <?php if ($eventPoster): ?>
            <img src="<?= $eventPoster ?>" style="width: 100%; max-height: 400px; object-fit: cover;">
        <?php endif; ?>
        <div style="padding: 30px;">
            <div class="event-detail-badges">
                <span class="event-status-badge badge-<?= htmlspecialchars($event['status']) ?>"><?= ucfirst($event['status']) ?></span>
                <span class="event-category-chip"><?= htmlspecialchars($event['category']) ?></span>
            </div>
            <h1 class="event-detail-title"><?= htmlspecialchars($event['title']) ?></h1>
            <div class="event-detail-meta">
                <div><i class="fas fa-users"></i> <?= htmlspecialchars($event['society_name']) ?></div>
                <div><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?></div>
                <div><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($event['start_time'])) ?><?= $event['end_time'] ? ' - ' . date('h:i A', strtotime($event['end_time'])) : '' ?></div>
                <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue']) ?></div>
            </div>
            <div class="event-detail-description">
                <h3>About this event</h3>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
            </div>

            <div class="event-detail-actions">
                <button class="action-btn like-btn <?= $liked ? 'liked' : '' ?>" data-id="<?= $event['id'] ?>">
                    <i class="fas fa-heart"></i> <span class="likes-count"><?= $like_count ?></span> Like
                </button>
                <button class="action-btn save-btn <?= $saved ? 'saved' : '' ?>" data-id="<?= $event['id'] ?>">
                    <i class="fas fa-bookmark"></i> <span class="saves-count"><?= $save_count ?></span> Save
                </button>
            </div>

            <div class="registration-card">
                <?php if (!empty($event['registration_link'])): ?>
                    <div>
                        <h3>Registration required</h3>
                        <p>Use the link below to secure your spot for this event.</p>
                    </div>
                    <a href="<?= htmlspecialchars($event['registration_link']) ?>" target="_blank" class="btn btn-primary">Register now</a>
                <?php else: ?>
                    <div>
                        <h3>Open event</h3>
                        <p>No registration is needed. Just turn up and enjoy the session.</p>
                    </div>
                    <span class="registration-pill"><i class="fas fa-check-circle"></i> Free event</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/event.js"></script>
<?php require_once 'includes/footer.php'; ?>