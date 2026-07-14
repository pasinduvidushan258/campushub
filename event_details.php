
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

$eventComments = [];
$commentReplies = [];

try {
    $hasCommentsTable = (bool) $pdo->query("SHOW TABLES LIKE 'event_comments'")->fetchColumn();

    if ($hasCommentsTable) {
        $commentStmt = $pdo->prepare("SELECT c.id, c.user_id, c.parent_comment_id, c.content, c.created_at, u.fullname
                                      FROM event_comments c
                                      INNER JOIN users u ON u.id = c.user_id
                                      WHERE c.event_id = ?
                                      ORDER BY c.created_at ASC");
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
    // Comments are optional; keep page alive if table/query fails.
}
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

            <div class="registration-card" style="margin-top: 18px; display: block;">
                <div class="registration-info" style="margin-bottom: 12px;">
                    <h3>
                        <i class="fas fa-comments"></i>
                        Discussion
                    </h3>
                    <p>Share thoughts, reply to comments, and mention users with @name.</p>
                </div>

                <?php if (isLoggedIn()): ?>
                    <form id="eventCommentForm" style="display: grid; gap: 10px; margin-bottom: 14px;">
                        <input type="hidden" id="parentCommentId" value="">
                        <textarea id="eventCommentInput" rows="3" placeholder="Write a comment..." style="border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.04); color: #e5e7eb; padding: 10px; resize: vertical;"></textarea>
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" id="clearReplyBtn" style="display:none; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #cbd5e1; border-radius: 8px; padding: 8px 12px; cursor: pointer;">Cancel reply</button>
                            <button type="submit" style="border: none; background: #f97316; color: white; border-radius: 8px; padding: 8px 14px; font-weight: 600; cursor: pointer;">Post Comment</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p style="color: #94a3b8; margin-bottom: 14px;">Login to join this discussion.</p>
                <?php endif; ?>

                <?php if (!empty($eventComments)): ?>
                    <div style="display: grid; gap: 10px;">
                        <?php foreach ($eventComments as $comment): ?>
                            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 10px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:6px;">
                                    <strong style="color:#f8fafc;"><?= htmlspecialchars($comment['fullname']) ?></strong>
                                    <span style="font-size:0.75rem; color:#94a3b8;"><?= date('d M Y h:i A', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <p style="margin: 0; color: #cbd5e1;"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                <?php if (isLoggedIn()): ?>
                                    <button type="button" class="reply-btn" data-parent-id="<?= (int) $comment['id'] ?>" data-parent-user="<?= htmlspecialchars($comment['fullname']) ?>" style="margin-top:8px; border:none; background:transparent; color:#f97316; cursor:pointer; font-size:0.84rem; padding:0;">Reply</button>
                                <?php endif; ?>

                                <?php
                                $replies = array_filter($commentReplies, static function ($r) use ($comment) {
                                    return (int) $r['parent_comment_id'] === (int) $comment['id'];
                                });
                                ?>

                                <?php if (!empty($replies)): ?>
                                    <div style="margin-top: 10px; display: grid; gap: 8px;">
                                        <?php foreach ($replies as $reply): ?>
                                            <div style="margin-left: 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 9px; padding: 8px;">
                                                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:5px;">
                                                    <strong style="color:#e2e8f0;"><?= htmlspecialchars($reply['fullname']) ?></strong>
                                                    <span style="font-size:0.72rem; color:#94a3b8;"><?= date('d M Y h:i A', strtotime($reply['created_at'])) ?></span>
                                                </div>
                                                <p style="margin: 0; color: #cbd5e1;"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #94a3b8;">No comments yet. Be the first to start the discussion.</p>
                <?php endif; ?>
            </div>

        </div>

    </div>
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
