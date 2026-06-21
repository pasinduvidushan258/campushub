<?php
require_once 'includes/header.php';
require_once 'includes/function.php';
require_once 'config/database.php';

$search = $_GET['search'] ?? '';

$sql = "SELECT s.*, (SELECT COUNT(*) FROM events WHERE society_id = s.id) AS total_events
        FROM societies s
        WHERE s.status = 'verified'";

$params = [];
if (!empty($search)) {
    $sql     .= " AND (s.society_name LIKE ? OR s.faculty LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}
$sql .= " ORDER BY s.society_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$societies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container" style="max-width: 1300px; margin: 30px auto; padding: 0 20px;">
    <h1>Student Societies</h1>
    <div class="filter-bar">
        <input type="text" id="societySearch" placeholder="Search by name or faculty..." value="<?= htmlspecialchars($search) ?>">
        <button id="searchBtn" class="btn btn-primary">Search</button>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
        <?php foreach ($societies as $soc): ?>
            <div class="society-card" style="background: #242526; border-radius: 16px; overflow: hidden; text-align: center; padding: 20px;">
                <?php if ($soc['logo_path']): ?>
                    <img src="/campushub/uploads/societies/<?= $soc['logo_path'] ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px;">
                <?php else: ?>
                    <div style="width: 100px; height: 100px; background: #F97316; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">🏛️</div>
                <?php endif; ?>
                <h3><?= htmlspecialchars($soc['society_name']) ?></h3>
                <p style="color: #B0B3B8;"><?= htmlspecialchars($soc['faculty'] ?? 'Faculty not set') ?></p>
                <p>📅 <?= $soc['total_events'] ?> Events</p>
                <a href="events.php?society_id=<?= $soc['id'] ?>" class="btn btn-primary">View Events</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('searchBtn').addEventListener('click', function () {
    let search = document.getElementById('societySearch').value;
    window.location.href = `societies.php?search=${encodeURIComponent(search)}`;
});
</script>

<?php require_once 'includes/footer.php'; ?>