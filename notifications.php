<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/notification_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
campushub_seed_due_event_reminders($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    if (campushub_notifications_table_ready($pdo)) {
        $markAllStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ? AND is_read = 0");
        $markAllStmt->execute([$userId]);
    }
    header('Location: notifications.php');
    exit;
}

$notifications = [];
if (campushub_notifications_table_ready($pdo)) {
    $stmt = $pdo->prepare("SELECT id, type, title, message, link_url, is_read, created_at
                           FROM notifications
                           WHERE recipient_user_id = ?
                           ORDER BY is_read ASC, created_at DESC
                           LIMIT 200");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$unreadCount = campushub_get_unread_notification_count($pdo, $userId);

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/style.css">

<div style="max-width: 980px; margin: 96px auto 24px; padding: 0 14px;">
    <div style="background: linear-gradient(160deg, rgba(22,28,38,0.95), rgba(18,22,30,0.95)); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; box-shadow: 0 18px 44px rgba(0,0,0,0.35); overflow: hidden;">
        <div style="padding: 16px 18px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div>
                <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = 'index.php'; }" style="display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(148,163,184,0.35); background: rgba(148,163,184,0.1); color: #cbd5e1; border-radius: 999px; padding: 6px 12px; font-size: 0.82rem; font-weight: 600; cursor: pointer; margin-bottom: 10px;">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </button>
                <h1 style="margin: 0; color: #e5e7eb; font-size: 1.5rem;">All Notifications</h1>
                <p style="margin: 4px 0 0; color: #94a3b8; font-size: 0.9rem;">Unread: <?= (int) $unreadCount ?></p>
            </div>
            <form method="POST" action="notifications.php" style="margin: 0;">
                <input type="hidden" name="mark_all" value="1">
                <button type="submit" style="border: 1px solid rgba(249, 115, 22, 0.45); background: rgba(249, 115, 22, 0.14); color: #fdba74; border-radius: 999px; padding: 8px 14px; font-weight: 600; cursor: pointer;">Mark all as read</button>
            </form>
        </div>

        <div style="max-height: 72vh; overflow-y: auto; padding: 8px 10px 12px;">
            <?php if (empty($notifications)): ?>
                <div style="padding: 22px 12px; text-align: center; color: #94a3b8;">No notifications yet.</div>
            <?php else: ?>
                <?php foreach ($notifications as $note): ?>
                    <?php
                    $isRead = ((int) ($note['is_read'] ?? 0) === 1);
                    $cardBg = $isRead ? 'rgba(255,255,255,0.03)' : 'rgba(59,130,246,0.12)';
                    $dotBg = $isRead ? 'transparent' : '#60a5fa';
                    $dotBorder = $isRead ? '1px solid rgba(255,255,255,0.25)' : 'none';
                    $target = trim((string) ($note['link_url'] ?? ''));
                    if ($target === '') {
                        $target = '#';
                    }
                    ?>
                    <a href="<?= htmlspecialchars($target) ?>" class="notif-page-item" data-id="<?= (int) $note['id'] ?>" style="display: flex; gap: 10px; text-decoration: none; margin-top: 8px; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 11px; background: <?= $cardBg ?>; color: #e5e7eb;">
                        <span style="width: 8px; height: 8px; margin-top: 8px; border-radius: 50%; background: <?= $dotBg ?>; border: <?= $dotBorder ?>; flex: 0 0 auto;"></span>
                        <span style="display: grid; gap: 4px; min-width: 0;">
                            <strong style="font-size: 0.95rem;"><?= htmlspecialchars((string) ($note['title'] ?? 'CampusHub Update')) ?></strong>
                            <span style="color: #cbd5e1; font-size: 0.87rem; line-height: 1.38;"><?= htmlspecialchars((string) ($note['message'] ?? '')) ?></span>
                            <span style="color: #94a3b8; font-size: 0.78rem;"><?= htmlspecialchars(timeAgo($note['created_at'] ?? null)) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const items = document.querySelectorAll('.notif-page-item[data-id]');
    items.forEach((item) => {
        item.addEventListener('click', async function () {
            const id = Number(this.getAttribute('data-id') || 0);
            if (id <= 0) {
                return;
            }

            try {
                await fetch('notifications_mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: id })
                });
            } catch (_) {
                // Keep navigation smooth even if read-state update fails.
            }
        });
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
