<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/notification_helpers.php'; // For timeAgo()

// Determine if current user can post
$canPost = false;
$active_mode = $_SESSION['active_mode'] ?? 'user';
$author_avatar = '';

if (isset($_SESSION['user_id'])) {
    if ($active_mode === 'society' && isset($_SESSION['active_society_id'])) {
        $canPost = true;
        // Get society logo
        $stmt = $pdo->prepare("SELECT logo_path FROM societies WHERE id = ?");
        $stmt->execute([$_SESSION['active_society_id']]);
        $soc = $stmt->fetch();
        if ($soc && !empty($soc['logo_path'])) {
            $author_avatar = 'assets/images/uploads/' . $soc['logo_path'];
        }
    } else {
        // Check if user is admin or lecturer
        $stmt = $pdo->prepare("SELECT category, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && in_array((string) $user['category'], ['admin', 'lecturer'], true)) {
            $canPost = true;
            $author_avatar = $user['avatar_url'];
        }
    }
}

// Fetch all notices. Keep the page alive even if DB table is missing/misconfigured.
$notices = [];
$notice_fetch_error = '';

try {
        $sql = "SELECT n.*, 
            u.fullname as admin_name, u.avatar_url as admin_avatar, u.category as admin_category,
            s.society_name, s.logo_path as society_logo
            FROM notices n
            LEFT JOIN users u ON n.author_type = 'admin' AND n.author_id = u.id
            LEFT JOIN societies s ON n.author_type = 'society' AND n.author_id = s.id
            ORDER BY n.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[notices.php] Failed to fetch notices: ' . $e->getMessage());
    $notice_fetch_error = 'Unable to load notices right now. Please try again later.';
}
?>

<link rel="stylesheet" href="assets/css/notices.css">

<div class="notices-page">
    <div class="notices-page-header">
        <h1><i class="fas fa-bullhorn" style="color: #F97316; margin-right: 10px;"></i>Campus Notices</h1>
    </div>

    <?php if (!empty($notice_fetch_error)): ?>
        <div class="notice-alert-error"><?= htmlspecialchars($notice_fetch_error) ?></div>
    <?php endif; ?>

    <?php if ($canPost): ?>
    <div class="post-notice-card">
        <?php if (!empty($author_avatar) && $author_avatar !== 'assets/images/default_avatar.png'): ?>
            <img src="<?= htmlspecialchars($author_avatar) ?>" class="post-author-avatar" alt="Author">
        <?php else: ?>
            <div class="post-author-avatar"><i class="fas <?= $active_mode === 'society' ? 'fa-users' : 'fa-user-shield' ?>"></i></div>
        <?php endif; ?>

        <div class="post-input-wrapper">
            <form id="postNoticeForm">
                <textarea id="noticeInput" placeholder="Share an announcement or update..." required></textarea>
                <div class="post-notice-fields">
                    <div class="notice-field">
                        <label for="noticeCategory">Category</label>
                        <select id="noticeCategory" name="category">
                            <option value="general" selected>General</option>
                            <option value="academic">Academic</option>
                            <option value="event">Event</option>
                            <option value="society">Society</option>
                            <option value="deadline">Deadline</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="notice-field">
                        <label for="noticePriority">Priority</label>
                        <select id="noticePriority" name="priority">
                            <option value="normal" selected>Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="notice-field">
                        <label for="noticeExpiry">Expiry (optional)</label>
                        <input id="noticeExpiry" name="expiry_date" type="datetime-local">
                    </div>
                </div>

                <div class="post-actions">
                    <span class="char-counter" id="charCounter">0 / 500</span>
                    <button type="submit" class="btn-post" id="postBtn" disabled>
                        <i class="fas fa-paper-plane"></i> Post Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="notices-feed" id="noticesFeed">
        <?php if (empty($notices)): ?>
            <div class="notices-empty">
                <i class="fas fa-comment-slash"></i>
                <h3>No notices yet</h3>
                <p>Campus announcements and society updates will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notices as $n): 
                $is_admin = $n['author_type'] === 'admin';
                $author_name = $is_admin ? $n['admin_name'] : $n['society_name'];
                $avatar_path = $is_admin ? $n['admin_avatar'] : (!empty($n['society_logo']) ? 'assets/images/uploads/' . $n['society_logo'] : '');
                $badge_class = $is_admin ? 'type-badge-admin' : 'type-badge-society';
                $admin_category = strtolower(trim((string) ($n['admin_category'] ?? 'admin')));
                $badge_text = $is_admin ? ($admin_category === 'lecturer' ? 'Lecturer' : 'Admin') : 'Society';
                $icon_class = $is_admin ? 'fa-user-shield' : 'fa-users';
                $priority = strtolower(trim((string) ($n['priority'] ?? 'normal')));
                if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
                    $priority = 'normal';
                }

                $category = strtolower(trim((string) ($n['category'] ?? 'general')));
                if ($category === '') {
                    $category = 'general';
                }

                $expiry_raw = trim((string) ($n['expiry_date'] ?? ''));
                $expiry_text = '';
                $is_expired = false;
                if ($expiry_raw !== '') {
                    try {
                        $expiry_dt = new DateTime($expiry_raw);
                        $is_expired = $expiry_dt < new DateTime();
                        $expiry_text = $expiry_dt->format('d M Y, h:i A');
                    } catch (Throwable $e) {
                        $expiry_text = '';
                    }
                }
            ?>
                <div class="notice-card">
                    <?php if (!empty($avatar_path) && $avatar_path !== 'assets/images/default_avatar.png'): ?>
                        <img src="<?= htmlspecialchars($avatar_path) ?>" class="notice-avatar" alt="Avatar">
                    <?php else: ?>
                        <div class="notice-avatar"><i class="fas <?= $icon_class ?>"></i></div>
                    <?php endif; ?>

                    <div class="notice-content">
                        <div class="notice-header">
                            <h4 class="notice-author"><?= htmlspecialchars($author_name ?? 'Unknown') ?></h4>
                            <span class="notice-author-type <?= $badge_class ?>"><?= $badge_text ?></span>
                            <span class="notice-time"><?= timeAgo($n['created_at']) ?></span>
                        </div>
                        <div class="notice-meta-row">
                            <span class="notice-pill notice-category"><?= htmlspecialchars(ucfirst($category)) ?></span>
                            <span class="notice-pill notice-priority-<?= htmlspecialchars($priority) ?>"><?= htmlspecialchars(ucfirst($priority)) ?></span>
                            <?php if ($expiry_text !== ''): ?>
                                <span class="notice-pill <?= $is_expired ? 'notice-expired' : 'notice-expiry' ?>"><?php echo $is_expired ? 'Expired: ' : 'Expires: '; ?><?= htmlspecialchars($expiry_text) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="notice-text"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/notices.js"></script>

<?php require_once 'includes/footer.php'; ?>
