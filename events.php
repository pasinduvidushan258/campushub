<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$category   = $_GET['category']   ?? '';
$status     = $_GET['status']     ?? '';
$search     = $_GET['search']     ?? '';
$society_id = (int) ($_GET['society_id'] ?? 0);

$categories = ['Workshop','Seminar','Competition','Sports','Cultural','Music','Technology','Career','Volunteer','Other'];
$statuses   = ['upcoming','ongoing','completed'];

// Reject any value that isn't in the allowed list.
if (!in_array($category, $categories, true)) { $category = ''; }
if (!in_array($status,   $statuses,   true)) { $status   = ''; }

$where  = ["1=1"];
$params = [];

if (!empty($category)) {
    $where[]  = "e.category = ?";
    $params[] = $category;
}
if (!empty($status)) {
    $where[]  = "e.status = ?";
    $params[] = $status;
}
if (!empty($search)) {
    $where[]  = "(e.title LIKE ? OR s.society_name LIKE ?)";
    $search_param = "$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}
if ($society_id > 0) {
    $where[]  = "e.society_id = ?";
    $params[] = $society_id;
}
// Build the final SQL query with dynamic WHERE clause
$sql = "SELECT e.*, s.society_name,
        (SELECT COUNT(*) FROM event_likes  WHERE event_id = e.id) AS likes_count,
        (SELECT COUNT(*) FROM saved_events WHERE event_id = e.id) AS saves_count
        FROM events e
        JOIN societies s ON e.society_id = s.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY e.event_date ASC, e.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/event.css">

<div class="events-page">
    <div class="events-page-header">
        <h1>Events</h1>
        <span class="events-count-badge"><?= count($events) ?> Event<?= count($events) === 1 ? '' : 's' ?> Found</span>
    </div>

    
    <div class="filter-bar">
        <div class="filter-search-wrap"><i class="fas fa-search search-ico"></i>

            <input
            type="text"
            id="searchInput"
            placeholder="Search events or society..."
            value="<?= htmlspecialchars($search) ?>"
            autocomplete="off">

            <div id="searchSuggestions" class="search-suggestions"></div>
        </div>

        <div class="filter-select-wrap">
            <i class="fas fa-tag select-ico"></i>
            <select id="categorySelect" class="<?= $category ? 'has-value' : '' ?>">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-select-wrap">
            <i class="fas fa-circle-dot select-ico"></i>
            <select id="statusSelect" class="<?= $status ? 'has-value' : '' ?>">
                <option value="">All Status</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?= htmlspecialchars($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-divider"></div>

        <div class="filter-actions">
            <button id="applyFilters" class="btn-filter-apply"><i class="fas fa-filter"></i> Apply</button>
            <a href="events.php" class="btn-filter-reset"><i class="fas fa-rotate-left"></i> Reset</a>
        </div>
    </div>

    <?php if ($category || $status || $search): ?>
        <div class="active-filter-pills">
            <?php if ($search): ?>
                <span class="filter-pill">
                    Search: "<?= htmlspecialchars($search) ?>"
                    <a href="events.php?category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>"><i class="fas fa-times"></i></a>
                </span>
            <?php endif; ?>
            <?php if ($category): ?>
                <span class="filter-pill">
                    <?= htmlspecialchars($category) ?>
                    <a href="events.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><i class="fas fa-times"></i></a>
                </span>
            <?php endif; ?>
            <?php if ($status): ?>
                <span class="filter-pill">
                    <?= ucfirst($status) ?>
                    <a href="events.php?search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"><i class="fas fa-times"></i></a>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="events-grid">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
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
                            <div class="event-meta-row"><i class="fas fa-users"></i><a href="society_profile.php?id=<?= $event['society_id'] ?>"
                                class="action-btn-details"><?= htmlspecialchars($event['society_name']) ?></a></div>
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
        <?php else: ?>
            <div class="events-empty">
                <i class="fas fa-calendar-xmark"></i>
                <h3>No events found</h3>
                <p>Try adjusting your filters or search term.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/event.js"></script>


<?php require_once 'includes/footer.php'; ?>