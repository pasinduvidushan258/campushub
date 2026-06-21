<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$category   = $_GET['category']   ?? '';
$status     = $_GET['status']     ?? '';
$search     = $_GET['search']     ?? '';
$society_id = $_GET['society_id'] ?? 0;

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
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}
if ($society_id > 0) {
    $where[]  = "e.society_id = ?";
    $params[] = $society_id;
}

$sql = "SELECT e.*, s.society_name,
        (SELECT COUNT(*) FROM event_likes  WHERE event_id = e.id) AS likes_count,
        (SELECT COUNT(*) FROM saved_events WHERE event_id = e.id) AS saves_count
        FROM events e
        JOIN societies s ON e.society_id = s.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY e.event_date ASC, e.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(empty($params) ? [] : $params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = ['Workshop','Seminar','Competition','Sports','Cultural','Music','Technology','Career','Volunteer','Other'];
$statuses   = ['upcoming','ongoing','completed'];
?>

<div class="container" style="max-width: 1300px; margin: 30px auto; padding: 0 20px;">
    <h1 style="font-size: 2rem; margin-bottom: 20px;">Events</h1>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search events or society..." value="<?= htmlspecialchars($search) ?>">
        <select id="categorySelect">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= $category == $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
        </select>
        <select id="statusSelect">
            <option value="">All Status</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>" <?= $status == $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <button id="applyFilters" class="btn btn-primary">Apply</button>
    </div>

    <div class="events-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card" style="background: #242526; border-radius: 16px; overflow: hidden; transition: transform 0.2s;">
                    <?php if ($event['poster_path']): ?>
                        <img src="/campushub/uploads/events/<?= $event['poster_path'] ?>" class="event-poster" style="width: 100%; height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <div style="height: 180px; background: #3A3B3C; display: flex; align-items: center; justify-content: center;">No Flyer</div>
                    <?php endif; ?>
                    <div style="padding: 16px;">
                        <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 6px;"><?= htmlspecialchars($event['title']) ?></h3>
                        <div style="font-size: 0.85rem; color: #B0B3B8; margin-bottom: 8px;">
                            <i class="fas fa-users"></i> <?= htmlspecialchars($event['society_name']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: #B0B3B8;">
                            <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?> at <?= date('h:i A', strtotime($event['start_time'])) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: #B0B3B8; margin-bottom: 12px;">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue']) ?>
                        </div>
                        <div style="display: flex; gap: 15px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px;">
                            <button class="action-btn like-btn <?= isLoggedIn() && userLikedEvent(getUserId(), $event['id'], $pdo) ? 'liked' : '' ?>" data-id="<?= $event['id'] ?>">
                                <i class="fas fa-heart"></i> <span class="likes-count"><?= $event['likes_count'] ?></span>
                            </button>
                            <button class="action-btn save-btn <?= isLoggedIn() && userSavedEvent(getUserId(), $event['id'], $pdo) ? 'saved' : '' ?>" data-id="<?= $event['id'] ?>">
                                <i class="fas fa-bookmark"></i> <span class="saves-count"><?= $event['saves_count'] ?></span>
                            </button>
                            <a href="event_details.php?id=<?= $event['id'] ?>" class="action-btn" style="text-decoration: none;">View Details →</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No events found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('applyFilters').addEventListener('click', function () {
    let search   = document.getElementById('searchInput').value;
    let category = document.getElementById('categorySelect').value;
    let status   = document.getElementById('statusSelect').value;
    window.location.href = `events.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&status=${encodeURIComponent(status)}`;
});
</script>

<?php require_once 'includes/footer.php'; ?>