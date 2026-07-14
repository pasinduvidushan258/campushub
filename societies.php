<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$search = $_GET['search'] ?? '';

$userId = isLoggedIn() ? getUserId() : 0;

$sql = "SELECT s.*,
        (SELECT COUNT(*) FROM events WHERE society_id = s.id) AS total_events,
        (SELECT COUNT(*) FROM events WHERE society_id = s.id AND status = 'upcoming') AS upcoming_events,
        (SELECT COUNT(*) FROM society_followers WHERE society_id = s.id) AS follower_count,
        (SELECT COUNT(*) FROM society_managers WHERE society_id = s.id) AS member_count,
        ? > 0 AND EXISTS (
            SELECT 1 FROM society_followers sf
            WHERE sf.society_id = s.id AND sf.user_id = ?
        ) AS is_following
        FROM societies s
        WHERE s.status = 'verified'";

$params = [$userId, $userId];
if (!empty($search)) {
    $sql     .= " AND (s.society_name LIKE ? OR s.faculty LIKE ? OR s.description LIKE ?)";
    $search_param = "$search%";// Only search for entries that start with the search term for better relevance
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}
$sql .= " ORDER BY s.society_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$societies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/event.css">

<div class="events-page">
    <div class="events-page-header">
        <h1>Societies</h1>
        <span class="events-count-badge"><?= count($societies) ?> Societ<?= count($societies) === 1 ? 'y' : 'ies' ?></span>
    </div>

    <form class="filter-bar" method="GET" action="societies.php" id="societySearchForm" novalidate>
        <div class="filter-search-wrap">
            <i class="fas fa-search search-ico"></i>
            <input type="text" id="societySearch" name="search" placeholder="Search societies by name, faculty, or about..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            <button type="button" id="clearSocietySearch" class="search-clear-btn<?= !empty($search) ? ' is-visible' : '' ?>" aria-label="Clear search">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </form>

    <?php if ($search): ?>
        <div class="active-filter-pills">
            <span class="filter-pill">
                Search: "<?= htmlspecialchars($search) ?>"
                <a href="societies.php"><i class="fas fa-times"></i></a>
            </span>
        </div>
    <?php endif; ?>

    <div class="society-grid">
        <?php if (!empty($societies)): ?>
            <?php foreach ($societies as $soc): ?>
                <?php
                    $societyCover = !empty($soc['cover_path']) && file_exists('assets/images/uploads/' . $soc['cover_path'])
                        ? 'assets/images/uploads/' . htmlspecialchars($soc['cover_path'])
                        : '';

                    $societyLogo = !empty($soc['logo_path']) && file_exists('assets/images/uploads/' . $soc['logo_path'])
                        ? 'assets/images/uploads/' . htmlspecialchars($soc['logo_path'])
                        : '';

                    $foundedLabel = !empty($soc['founded_date'])
                        ? date('M Y', strtotime($soc['founded_date']))
                        : 'Not set';

                    $societyAbout = trim((string)($soc['description'] ?? ''));
                    $websiteUrl = trim((string)($soc['website_url'] ?? ''));
                    $facebookUrl = trim((string)($soc['facebook_url'] ?? ''));
                    $instagramUrl = trim((string)($soc['instagram_link'] ?? ''));

                    $categoryLabel = trim((string)($soc['faculty'] ?? ''));
                    if ($categoryLabel === '') {
                        $categoryLabel = 'General';
                    }
                ?>
                <article class="society-card" data-society-id="<?= (int) $soc['id'] ?>">
                    <a href="society_profile.php?id=<?= (int) $soc['id'] ?>" class="society-cover-wrap">
                        <?php if ($societyCover): ?>
                            <img src="<?= $societyCover ?>" class="society-cover-image" alt="<?= htmlspecialchars($soc['society_name']) ?> cover">
                        <?php elseif ($societyLogo): ?>
                            <img src="<?= $societyLogo ?>" class="society-cover-image" alt="<?= htmlspecialchars($soc['society_name']) ?> logo">
                        <?php else: ?>
                            <div class="society-cover-placeholder"><i class="fas fa-users"></i></div>
                        <?php endif; ?>

                        <span class="society-category-chip"><?= htmlspecialchars($categoryLabel) ?></span>

                        <?php if ($soc['status'] === 'verified'): ?>
                            <span class="society-verified-badge"><i class="fas fa-circle-check"></i> Verified</span>
                        <?php endif; ?>
                    </a>

                    <div class="society-card-body">
                        <div class="society-card-header">
                            <div class="society-logo-wrap">
                                <?php if ($societyLogo): ?>
                                    <img src="<?= $societyLogo ?>" class="society-logo-image" alt="<?= htmlspecialchars($soc['society_name']) ?>">
                                <?php else: ?>
                                    <div class="society-logo-fallback"><i class="fas fa-university"></i></div>
                                <?php endif; ?>
                            </div>

                            <div class="society-name-wrap">
                                <h3 class="society-card-name">
                                    <a href="society_profile.php?id=<?= (int) $soc['id'] ?>"><?= htmlspecialchars($soc['society_name']) ?></a>
                                </h3>
                                <p class="society-founded"><i class="fas fa-calendar-alt"></i> Established <?= htmlspecialchars($foundedLabel) ?></p>
                            </div>
                        </div>

                        <p class="society-about"><?= $societyAbout !== '' ? htmlspecialchars($societyAbout) : 'No society description added yet.' ?></p>

                        <div class="society-stats-grid">
                            <div class="society-stat-box">
                                <i class="fas fa-users"></i>
                                <span class="stat-label">Followers</span>
                                <strong class="soc-followers-count"><?= (int) $soc['follower_count'] ?></strong>
                            </div>
                            <div class="society-stat-box">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="stat-label">Events</span>
                                <strong><?= (int) $soc['total_events'] ?></strong>
                            </div>
                            <div class="society-stat-box">
                                <i class="fas fa-hourglass-half"></i>
                                <span class="stat-label">Upcoming</span>
                                <strong><?= (int) $soc['upcoming_events'] ?></strong>
                            </div>
                            <div class="society-stat-box">
                                <i class="fas fa-user-friends"></i>
                                <span class="stat-label">Members</span>
                                <strong><?= (int) $soc['member_count'] ?></strong>
                            </div>
                        </div>

                        <div class="society-card-footer">
                            <div class="society-social-links">
                                <?php if ($websiteUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($websiteUrl) ?>" target="_blank" rel="noopener noreferrer" title="Website"><i class="fas fa-globe"></i></a>
                                <?php endif; ?>
                                <?php if ($facebookUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                                <?php endif; ?>
                                <?php if ($instagramUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" title="Instagram"><i class="fab fa-instagram"></i></a>
                                <?php endif; ?>
                            </div>

                            <div class="society-card-actions">
                                <button
                                    type="button"
                                    class="soc-follow-btn<?= !empty($soc['is_following']) ? ' is-following' : '' ?>"
                                    data-id="<?= (int) $soc['id'] ?>"
                                >
                                    <i class="fas <?= !empty($soc['is_following']) ? 'fa-check' : 'fa-plus' ?>"></i>
                                    <span class="follow-label"><?= !empty($soc['is_following']) ? 'Following' : 'Follow' ?></span>
                                </button>

                                <a href="society_profile.php?id=<?= (int) $soc['id'] ?>" class="action-btn-details">View Profile <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="events-empty">
                <i class="fas fa-users-slash"></i>
                <h3>No societies found</h3>
                <p>Try a different search term.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/societies.js"></script>

<?php require_once 'includes/footer.php'; ?>