
<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$event_id = $_GET['id'] ?? 0;

if (!$event_id) {
    header("Location: events.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.*, s.society_name
    FROM events e
    JOIN societies s ON e.society_id = s.id
    WHERE e.id = ?
");

$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='container'>Event not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

$liked = isLoggedIn() && userLikedEvent(getUserId(), $event_id, $pdo);
$saved = isLoggedIn() && userSavedEvent(getUserId(), $event_id, $pdo);

$eventPoster = '';

if (
    !empty($event['poster_path']) &&
    file_exists('assets/images/events/' . $event['poster_path'])
) {
    $eventPoster = 'assets/images/events/' . $event['poster_path'];
}

$like_count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM event_likes WHERE event_id = ?"
);
$like_count_stmt->execute([$event_id]);
$like_count = (int)$like_count_stmt->fetchColumn();

$save_count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM saved_events WHERE event_id = ?"
);
$save_count_stmt->execute([$event_id]);
$save_count = (int)$save_count_stmt->fetchColumn();
?>

<link rel="stylesheet" href="assets/css/event_details.css">

<div class="container event-detail-shell">
    <div class="event-detail-card">

        <!-- Event Flyer -->
        <div class="event-detail-banner">
            <?php if ($eventPoster): ?>
                <a href="<?= $eventPoster ?>" target="_blank">
                    <img src="<?= $eventPoster ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                </a>
            <?php else: ?>
                <div class="event-banner-placeholder">
                    <i class="fas fa-image"></i>
                    <span>No Event Flyer Available</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="event-detail-content">

            <div class="event-detail-badges">
                <span class="event-status-badge badge-<?= htmlspecialchars($event['status']) ?>">
                    <?= ucfirst($event['status']) ?>
                </span>

                <span class="event-category-chip">
                    <?= htmlspecialchars($event['category']) ?>
                </span>
            </div>

            <h1 class="event-detail-title">
                <?= htmlspecialchars($event['title']) ?>
            </h1>

            <div class="event-quick-info">

                <div class="info-box">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <strong>Date</strong>
                        <span><?= date('d M Y', strtotime($event['event_date'])) ?></span>
                    </div>
                </div>

                <div class="info-box">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Time</strong>
                        <span>
                            <?= date('h:i A', strtotime($event['start_time'])) ?>
                            <?= !empty($event['end_time']) ? ' - ' . date('h:i A', strtotime($event['end_time'])) : '' ?>
                        </span>
                    </div>
                </div>

                <div class="info-box">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <strong>Venue</strong>
                        <span><?= htmlspecialchars($event['venue']) ?></span>
                    </div>
                </div>

                <div class="info-box">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong>Organized By</strong>
                        <a href="society_profile.php?id=<?= $event['society_id'] ?>" class="society-btn">
                            <?= htmlspecialchars($event['society_name']) ?>
                        </a>
                    </div>
                </div>

            </div>

            <div class="event-detail-description">
                <h2>About This Event</h2>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
            </div>

            <div class="event-detail-actions">

    <button
        class="event-action-btn like-btn <?= $liked ? 'liked' : '' ?>"
        data-id="<?= $event['id'] ?>">

        <i class="fas fa-heart"></i>
        <span class="likes-count"><?= $like_count ?></span>
        Likes
    </button>

    <button
        class="event-action-btn save-btn <?= $saved ? 'saved' : '' ?>"
        data-id="<?= $event['id'] ?>">

        <i class="fas fa-bookmark"></i>
        <span class="saves-count"><?= $save_count ?></span>
        Saved
    </button>

</div>

            <div class="registration-card">

    <?php if (
        !empty($event['ticket_required']) &&
        $event['ticket_required'] == 1
    ): ?>

        <div class="registration-info">
            <h3>
                <i class="fas fa-ticket-alt"></i>
                Ticket Event
            </h3>

            <?php if (!empty($event['ticket_price'])): ?>
                <p>Ticket Price: Rs. <?= number_format($event['ticket_price'], 2) ?></p>
            <?php endif; ?>
        </div>

        <a href="<?= htmlspecialchars($event['ticket_link']) ?>"
           target="_blank"
           class="ticket-btn">
            Buy Ticket
        </a>

    <?php endif; ?>


    <?php if (
        !empty($event['requires_registration']) &&
        $event['requires_registration'] == 1
    ): ?>

        <div class="registration-info">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                Registration Required
            </h3>

            <p>Please register before attending this event.</p>
        </div>

        <a href="<?= htmlspecialchars($event['registration_link']) ?>"
           target="_blank"
           class="register-btn">
            Register Now
        </a>

    <?php endif; ?>


    <?php if (
        empty($event['requires_registration']) &&
        empty($event['ticket_required'])
    ): ?>

        <div class="registration-info">
            <h3>
                <i class="fas fa-check-circle"></i>
                Open Event
            </h3>

            <p>No registration or tickets required.</p>
        </div>

        <span class="free-pill">
            Free Entry
        </span>

    <?php endif; ?>

</div>

        </div>

    </div>
</div>

<script src="assets/js/event.js"></script>

<?php require_once 'includes/footer.php'; ?>
