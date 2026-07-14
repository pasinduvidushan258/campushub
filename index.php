<?php
// Include the site header (navigation bar, meta tags, linked CSS files, opening HTML structure)
include 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

// Pull the latest upcoming/ongoing events so the homepage always reflects
// whatever societies have most recently added — no caching, no stale data.
$latestEventsStmt = $pdo->prepare("
    SELECT e.*, s.society_name,
        (SELECT COUNT(*) FROM event_likes  WHERE event_id = e.id) AS likes_count,
        (SELECT COUNT(*) FROM saved_events WHERE event_id = e.id) AS saves_count
    FROM events e
    JOIN societies s ON e.society_id = s.id
    WHERE e.status IN ('upcoming', 'ongoing')
    ORDER BY e.created_at DESC
    LIMIT 6
");
$latestEventsStmt->execute();
$latestEvents = $latestEventsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/event.css">

<main class="main-content">

<!-- Hero Section Component -->
    <?php include 'includes/hero.php'; ?>


    <!-- Primary content wrapper for the page layout -->
    <div class="feed-container">

        <div class="latest-events-section">
            <div class="latest-events-heading">
                <h2>Latest Events</h2>
                <a href="events.php" class="btn-secondary">View All</a>
            </div>

            <?php if (empty($latestEvents)): ?>
                <div class="events-empty">
                    <i class="fas fa-calendar-xmark"></i>
                    <h3>No events yet</h3>
                    <p>Check back soon — societies are adding new events regularly.</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($latestEvents as $event): ?>
                        <?php
                            $isLiked = isLoggedIn() && userLikedEvent(getUserId(), $event['id'], $pdo);
                            $isSaved = isLoggedIn() && userSavedEvent(getUserId(), $event['id'], $pdo);
                            $eventPoster = !empty($event['poster_path']) && file_exists('assets/images/events/' . $event['poster_path'])
                                ? 'assets/images/events/' . htmlspecialchars($event['poster_path'])
                                : '';
                        ?>
                        <article class="event-card">
                            <div class="event-poster-wrap">
                                <?php if ($eventPoster): ?>
                                    <img src="<?= $eventPoster ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                                <?php else: ?>
                                    <div class="event-poster-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>No Flyer</span>
                                    </div>
                                <?php endif; ?>
                                <span class="event-status-badge badge-<?= htmlspecialchars($event['status']) ?>"><?= ucfirst($event['status']) ?></span>
                                <span class="event-category-chip"><?= htmlspecialchars($event['category']) ?></span>
                            </div>

                            <div class="event-body">
                                <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-row"><i class="fas fa-users"></i><a href="society_profile.php?id=<?= $event['society_id'] ?>" class="society-link"><?= htmlspecialchars($event['society_name']) ?></a></div>
                                    <div class="event-meta-row"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?> at <?= date('h:i A', strtotime($event['start_time'])) ?></div>
                                    <div class="event-meta-row"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue']) ?></div>
                                </div>

                                <div class="event-footer">
                                    <button class="action-btn like-btn <?= $isLiked ? 'liked' : '' ?>" data-id="<?= $event['id'] ?>">
                                        <i class="fas fa-heart"></i> <span class="likes-count"><?= $event['likes_count'] ?></span>
                                    </button>
                                    <button class="action-btn save-btn <?= $isSaved ? 'saved' : '' ?>" data-id="<?= $event['id'] ?>">
                                        <i class="fas fa-bookmark"></i> <span class="saves-count"><?= $event['saves_count'] ?></span>
                                    </button>
                                    <a href="event_details.php?id=<?= $event['id'] ?>" class="action-btn-details">View Details</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>


<?php
// Include the site footer (closing HTML tags, JavaScript files, footer links and copyright)
include 'includes/footer.php';
?>