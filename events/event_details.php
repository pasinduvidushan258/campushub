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

$liked      = isLoggedIn() && userLikedEvent(getUserId(), $event_id, $pdo);
$saved      = isLoggedIn() && userSavedEvent(getUserId(), $event_id, $pdo);
$like_count = (int) $pdo->query("SELECT COUNT(*) FROM event_likes  WHERE event_id = $event_id")->fetchColumn();
$save_count = (int) $pdo->query("SELECT COUNT(*) FROM saved_events WHERE event_id = $event_id")->fetchColumn();
?>

<div class="container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <div style="background: #242526; border-radius: 20px; overflow: hidden;">
        <?php if ($event['poster_path']): ?>
            <img src="/campushub/uploads/events/<?= $event['poster_path'] ?>" style="width: 100%; max-height: 400px; object-fit: cover;">
        <?php endif; ?>
        <div style="padding: 30px;">
            <h1><?= htmlspecialchars($event['title']) ?></h1>
            <div style="display: flex; gap: 20px; margin: 15px 0; flex-wrap: wrap; color: #B0B3B8;">
                <div><i class="fas fa-users"></i> <?= htmlspecialchars($event['society_name']) ?></div>
                <div><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?></div>
                <div><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($event['start_time'])) ?>
                    <?= $event['end_time'] ? '- ' . date('h:i A', strtotime($event['end_time'])) : '' ?>
                </div>
                <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue']) ?></div>
                <div><i class="fas fa-tag"></i> <?= $event['category'] ?></div>
            </div>
            <div style="margin: 20px 0;">
                <strong>Description:</strong><br>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
            </div>

            <!-- Like & Save Buttons -->
            <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                <button class="action-btn like-btn <?= $liked ? 'liked' : '' ?>" data-id="<?= $event['id'] ?>">
                    <i class="fas fa-heart"></i> <span class="likes-count"><?= $like_count ?></span> Like
                </button>
                <button class="action-btn save-btn <?= $saved ? 'saved' : '' ?>" data-id="<?= $event['id'] ?>">
                    <i class="fas fa-bookmark"></i> <span class="saves-count"><?= $save_count ?></span> Save
                </button>
            </div>

            <!-- Registration -->
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px;">
                <?php if (!empty($event['registration_link'])): ?>
                    <a href="<?= htmlspecialchars($event['registration_link']) ?>" target="_blank" class="btn btn-primary">Register via Google Form</a>
                <?php else: ?>
                    <p><i class="fas fa-check-circle"></i> Free event – no registration required.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>