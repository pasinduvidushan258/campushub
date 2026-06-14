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

<link rel="stylesheet" href="assets/css/event.css">

<div class="events-page">
    <div class="events-page-header">
        <h1>Student Societies</h1>
        <span class="events-count-badge"><?= count($societies) ?> Societ<?= count($societies) === 1 ? 'y' : 'ies' ?></span>
    </div>

    <div class="filter-bar">
        <div class="filter-search-wrap">
            <i class="fas fa-search search-ico"></i>
            <input type="text" id="societySearch" placeholder="Search by name or faculty..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="filter-actions">
            <button id="searchBtn" class="btn-filter-apply"><i class="fas fa-filter"></i> Search</button>
            <a href="societies.php" class="btn-filter-reset"><i class="fas fa-rotate-left"></i> Reset</a>
        </div>
    </div>

    <?php if ($search): ?>
        <div class="active-filter-pills">
            <span class="filter-pill">
                Search: "<?= htmlspecialchars($search) ?>"
                <a href="societies.php"><i class="fas fa-times"></i></a>
            </span>
        </div>
    <?php endif; ?>

    <div class="society-list" style="display:flex; flex-direction:column; gap:12px;">
        <?php if (!empty($societies)): ?>
            <?php foreach ($societies as $soc): ?>
                <?php
                    $societyLogo = !empty($soc['logo_path']) && file_exists('assets/images/uploads/' . $soc['logo_path'])
                        ? 'assets/images/uploads/' . htmlspecialchars($soc['logo_path'])
                        : '';
                ?>
                <div class="society-row" style="display:flex; align-items:center; gap:16px; background:#242526; border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:14px 16px;">
                    <?php if ($societyLogo): ?>
                        <img src="<?= $societyLogo ?>" class="society-row-logo" alt="<?= htmlspecialchars($soc['society_name']) ?>">
                    <?php else: ?>
                        <div class="society-row-logo society-row-logo-placeholder">🏛️</div>
                    <?php endif; ?>

                    <div class="society-row-info" style="flex:1; min-width:0;">
                        <h3 class="society-row-name"><?= htmlspecialchars($soc['society_name']) ?></h3>
                        <p class="society-row-faculty"><?= htmlspecialchars($soc['faculty'] ?? 'Faculty not set') ?></p>
                    </div>

                    <div class="society-row-events" style="font-size:0.9rem; color:#B0B3B8; flex-shrink:0; white-space:nowrap;">
                        <i class="fas fa-calendar"></i> <?= (int) $soc['total_events'] ?> Event<?= $soc['total_events'] == 1 ? '' : 's' ?>
                    </div>

                    <a href="events.php?society_id=<?= (int) $soc['id'] ?>" class="action-btn-details">View Events <i class="fas fa-arrow-right"></i></a>
                </div>
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

<script>
document.getElementById('searchBtn').addEventListener('click', function () {
    let search = document.getElementById('societySearch').value;
    window.location.href = `societies.php?search=${encodeURIComponent(search)}`;
});
document.getElementById('societySearch').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>