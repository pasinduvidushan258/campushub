<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode(app_url('saved_events.php')));
    exit;
}

$user_id = getUserId();

$stmt = $pdo->prepare("
    SELECT e.*, s.society_name,
        (SELECT COUNT(*) FROM event_likes WHERE event_id = e.id) as likes_count,
        (SELECT COUNT(*) FROM saved_events WHERE event_id = e.id) as saves_count
    FROM saved_events se
    JOIN events e ON se.event_id = e.id
    JOIN societies s ON e.society_id = s.id
    WHERE se.user_id = ?
    ORDER BY se.created_at DESC
");
$stmt->execute([$user_id]);
$saved_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$saved_count = count($saved_events);
?>

<link rel="stylesheet" href="assets/css/event.css">

<div class="container saved-events-shell">
    <div class="saved-events-header saved-events-header--compact">
        <div class="saved-events-title-row">
            <h1>Saved Events</h1>
            <?php if ($saved_count > 0): ?>
                <span class="saved-events-count"><?php echo $saved_count; ?> saved</span>
            <?php endif; ?>
        </div>
        <a href="events.php" class="saved-events-cta saved-events-cta--header">
            <i class="fas fa-compass"></i>
            Browse Events
        </a>
    </div>

    <?php if (empty($saved_events)): ?>
        <div class="saved-events-empty">
            <i class="fas fa-bookmark"></i>
            <h3>No saved events yet</h3>
            <p>Click the bookmark icon on any event to save it here for quick access.</p>
            <a href="events.php" class="btn btn-primary">Explore Events</a>
        </div>
    <?php else: ?>
        <div class="events-grid saved-events-grid">
            <?php foreach ($saved_events as $event): ?>
                <?php
                    $eventPoster = !empty($event['poster_path']) && file_exists('assets/images/events/' . $event['poster_path'])
                        ? 'assets/images/events/' . htmlspecialchars($event['poster_path'])
                        : '';
                ?>
                <article class="event-card saved-event-card">
                    <a href="event_details.php?id=<?= $event['id'] ?>" class="event-poster-link">
                        <?php if ($eventPoster): ?>
                            <img src="<?= $eventPoster ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                        <?php else: ?>
                            <div class="event-poster-placeholder">
                                <i class="fas fa-image"></i>
                                <span>No Flyer</span>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="event-body">
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="event-meta">
                            <div class="event-meta-row"><i class="fas fa-users"></i> <?= htmlspecialchars($event['society_name']) ?></div>
                            <div class="event-meta-row"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?></div>
                        </div>
                        <div class="saved-event-actions">
                            <a href="event_details.php?id=<?= $event['id'] ?>" class="action-btn-details">View Details</a>
                            <button class="action-btn save-btn saved" data-id="<?= $event['id'] ?>">
                                <i class="fas fa-bookmark"></i>
                                <span class="saves-count"><?= $event['saves_count'] ?></span>
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>