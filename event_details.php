<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$event_id = (int) ($_GET['id'] ?? 0);

if ($event_id <= 0) {
    header('Location: events.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT e.*, s.society_name
     FROM events e
     INNER JOIN societies s ON e.society_id = s.id
     WHERE e.id = ?"
);
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='container' style='margin:40px auto;color:#cbd5e1;'>Event not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

$liked = isLoggedIn() && userLikedEvent(getUserId(), $event_id, $pdo);
$saved = isLoggedIn() && userSavedEvent(getUserId(), $event_id, $pdo);

$eventPoster = '';
if (!empty($event['poster_path']) && file_exists('assets/images/events/' . $event['poster_path'])) {
    $eventPoster = 'assets/images/events/' . $event['poster_path'];
}

$like_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_likes WHERE event_id = ?");
$like_count_stmt->execute([$event_id]);
$like_count = (int) $like_count_stmt->fetchColumn();

$save_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_events WHERE event_id = ?");
$save_count_stmt->execute([$event_id]);
$save_count = (int) $save_count_stmt->fetchColumn();

$eventComments = [];
$commentReplies = [];

try {
    $hasCommentsTable = (bool) $pdo->query("SHOW TABLES LIKE 'event_comments'")->fetchColumn();

    if ($hasCommentsTable) {
        $commentStmt = $pdo->prepare(
            "SELECT c.id, c.user_id, c.parent_comment_id, c.content, c.created_at, u.fullname
             FROM event_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.event_id = ?
             ORDER BY c.created_at ASC"
        );
        $commentStmt->execute([$event_id]);
        $rows = $commentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            if (!empty($row['parent_comment_id'])) {
                $commentReplies[] = $row;
            } else {
                $eventComments[] = $row;
            }
        }
    }
} catch (Throwable $e) {
    // Optional feature: keep details page available even if comments table is missing.
}
?>

<link rel="stylesheet" href="assets/css/event_details.css">

<div class="container event-detail-shell">
    <article class="event-detail-card">
        <div class="event-detail-banner">
            <?php if ($eventPoster): ?>
                <a href="<?= htmlspecialchars($eventPoster) ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?= htmlspecialchars($eventPoster) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                </a>
            <?php else: ?>
                <div class="event-banner-placeholder">
                    <i class="fas fa-image"></i>
                    <span>No Event Flyer Available</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="event-detail-content">
            <a href="events.php" class="event-back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Events
            </a>

            <div class="event-detail-badges">
                <span class="event-status-badge badge-<?= htmlspecialchars($event['status']) ?>">
                    <?= ucfirst($event['status']) ?>
                </span>
                <span class="event-category-chip"><?= htmlspecialchars($event['category']) ?></span>
            </div>

            <h1 class="event-detail-title"><?= htmlspecialchars($event['title']) ?></h1>

            <p class="event-subtitle">
                Hosted by
                <a href="society_profile.php?id=<?= (int) $event['society_id'] ?>" class="society-btn">
                    <?= htmlspecialchars($event['society_name']) ?>
                </a>
            </p>

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
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <strong>Engagement</strong>
                        <span><?= $like_count ?> likes - <?= $save_count ?> saves</span>
                    </div>
                </div>
            </div>

            <section class="event-detail-description">
                <h2>About This Event</h2>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
            </section>

            <div class="event-detail-actions">
                <button class="event-action-btn like-btn <?= $liked ? 'liked' : '' ?>" data-id="<?= (int) $event['id'] ?>">
                    <i class="fas fa-heart"></i>
                    <span class="likes-count"><?= $like_count ?></span>
                    Likes
                </button>

                <button class="event-action-btn save-btn <?= $saved ? 'saved' : '' ?>" data-id="<?= (int) $event['id'] ?>">
                    <i class="fas fa-bookmark"></i>
                    <span class="saves-count"><?= $save_count ?></span>
                    Saved
                </button>
            </div>

            <section class="registration-card">
                <?php if (!empty($event['ticket_required']) && (int)$event['ticket_required'] === 1): ?>
                    <div class="registration-info">
                        <h3><i class="fas fa-ticket-alt"></i> Ticket Event</h3>
                        <?php if (!empty($event['ticket_price'])): ?>
                            <p>Ticket Price: Rs. <?= number_format((float)$event['ticket_price'], 2) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= htmlspecialchars($event['ticket_link']) ?>" target="_blank" rel="noopener noreferrer" class="ticket-btn">Buy Ticket</a>
                <?php endif; ?>

                <?php if (!empty($event['requires_registration']) && (int)$event['requires_registration'] === 1): ?>
                    <div class="registration-info">
                        <h3><i class="fas fa-clipboard-list"></i> Registration Required</h3>
                        <p>Please register before attending this event.</p>
                    </div>
                    <a href="<?= htmlspecialchars($event['registration_link']) ?>" target="_blank" rel="noopener noreferrer" class="register-btn">Register Now</a>
                <?php endif; ?>

                <?php if (empty($event['requires_registration']) && empty($event['ticket_required'])): ?>
                    <div class="registration-info">
                        <h3><i class="fas fa-check-circle"></i> Open Event</h3>
                        <p>No registration or tickets required.</p>
                    </div>
                    <span class="free-pill">Free Entry</span>
                <?php endif; ?>
            </section>

            <section class="discussion-card">
                <div class="discussion-header">
                    <h3><i class="fas fa-comments"></i> Discussion</h3>
                    <p>Share thoughts, reply to comments, and mention users with @name.</p>
                </div>

                <?php if (isLoggedIn()): ?>
                    <form id="eventCommentForm" class="event-comment-form">
                        <input type="hidden" id="parentCommentId" value="">
                        <textarea id="eventCommentInput" rows="3" placeholder="Write a comment..."></textarea>
                        <div class="comment-form-actions">
                            <button type="button" id="clearReplyBtn" class="comment-btn comment-btn-muted" style="display:none;">Cancel reply</button>
                            <button type="submit" class="comment-btn comment-btn-primary">Post Comment</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="discussion-empty-text">Login to join this discussion.</p>
                <?php endif; ?>

                <?php if (!empty($eventComments)): ?>
                    <div class="comments-list">
                        <?php foreach ($eventComments as $comment): ?>
                            <article class="comment-item">
                                <div class="comment-top-row">
                                    <strong><?= htmlspecialchars($comment['fullname']) ?></strong>
                                    <span><?= date('d M Y h:i A', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>

                                <?php if (isLoggedIn()): ?>
                                    <button
                                        type="button"
                                        class="reply-btn"
                                        data-parent-id="<?= (int) $comment['id'] ?>"
                                        data-parent-user="<?= htmlspecialchars($comment['fullname']) ?>">
                                        Reply
                                    </button>
                                <?php endif; ?>

                                <?php
                                $replies = array_filter($commentReplies, static function ($r) use ($comment) {
                                    return (int) $r['parent_comment_id'] === (int) $comment['id'];
                                });
                                ?>

                                <?php if (!empty($replies)): ?>
                                    <div class="reply-list">
                                        <?php foreach ($replies as $reply): ?>
                                            <div class="reply-item">
                                                <div class="comment-top-row">
                                                    <strong><?= htmlspecialchars($reply['fullname']) ?></strong>
                                                    <span><?= date('d M Y h:i A', strtotime($reply['created_at'])) ?></span>
                                                </div>
                                                <p><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="discussion-empty-text">No comments yet. Be the first to start the discussion.</p>
                <?php endif; ?>
            </section>
        </div>
    </article>
</div>

<script>
(function () {
    const form = document.getElementById('eventCommentForm');
    const input = document.getElementById('eventCommentInput');
    const parentInput = document.getElementById('parentCommentId');
    const clearBtn = document.getElementById('clearReplyBtn');
    const replyButtons = document.querySelectorAll('.reply-btn');

    if (replyButtons.length && input && parentInput && clearBtn) {
        replyButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const parentId = btn.getAttribute('data-parent-id') || '';
                const parentUser = btn.getAttribute('data-parent-user') || '';
                parentInput.value = parentId;
                clearBtn.style.display = 'inline-block';
                input.focus();
                if (!input.value.trim() && parentUser) {
                    input.value = '@' + parentUser.replace(/\s+/g, '') + ' ';
                }
            });
        });

        clearBtn.addEventListener('click', () => {
            parentInput.value = '';
            clearBtn.style.display = 'none';
        });
    }

    if (!form || !input || !parentInput) {
        return;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const content = input.value.trim();
        if (!content) {
            return;
        }

        const payload = new URLSearchParams();
        payload.append('event_id', String(<?= (int) $event_id ?>));
        payload.append('content', content);

        if (parentInput.value) {
            payload.append('parent_comment_id', parentInput.value);
        }

        try {
            const res = await fetch('add_event_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload.toString()
            });

            const data = await res.json();
            if (data && data.success) {
                window.location.reload();
                return;
            }

            alert((data && data.message) ? data.message : 'Unable to post comment.');
        } catch (_) {
            alert('Unable to post comment right now.');
        }
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
