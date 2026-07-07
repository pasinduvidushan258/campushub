<?php
// hero.php
// Pulls the most recent upcoming/ongoing events that have a poster image,
// so the homepage hero grid always reflects real, current events instead
// of a hardcoded placeholder list.
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

$heroEventImages = [];
$societiesCount = 0;
$eventsCount = 0;
$usersCount = 0;

try {
    $societiesStmt = $pdo->prepare("SELECT COUNT(*) FROM societies");
    $societiesStmt->execute();
    $societiesCount = (int) $societiesStmt->fetchColumn();

    $eventsStmt = $pdo->prepare("SELECT COUNT(*) FROM events");
    $eventsStmt->execute();
    $eventsCount = (int) $eventsStmt->fetchColumn();

    $usersStmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $usersStmt->execute();
    $usersCount = (int) $usersStmt->fetchColumn();

    $heroStmt = $pdo->prepare("
        SELECT poster_path
        FROM events
        WHERE poster_path IS NOT NULL
          AND poster_path != ''
          AND event_date >= CURDATE()
        ORDER BY event_date ASC, start_time ASC
        LIMIT 6
    ");
    $heroStmt->execute();

    foreach ($heroStmt->fetchAll(PDO::FETCH_COLUMN) as $posterFile) {
        if (file_exists(__DIR__ . '/../assets/images/events/' . $posterFile)) {
            $heroEventImages[] = 'assets/images/events/' . $posterFile;
        }
    }
} catch (PDOException $e) {
    error_log('[hero.php] Failed to load hero data: ' . $e->getMessage());
}
?>
<!-- Hero Section add the css -->
<link rel="stylesheet" href="assets/css/hero.css">

<section class="hero-section">

    <!-- Left Side: Description and Buttons -->
    <div class="hero-left">
        <h1 class="hero-title">Experience Campus Life Like Never Before</h1>
        <p class="hero-subtitle">
            CampusHub is your ultimate platform to discover exciting events, join amazing student societies, and stay updated with the latest university notices. All in one place!
        </p>



        <div class="hero-buttons">
            <a href="events.php" class="btn-primary">Explore Events</a>
            <a href="societies.php" class="btn-secondary">View Societies</a>
        </div>

        <div class="hero-stats">
            <!-- Box 1: Societies -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($societiesCount) ?></h3>
                    <p>Societies</p>
                </div>
            </div>

            <!-- Box 2: Events -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($eventsCount) ?></h3>
                    <p>Events</p>
                </div>
            </div>

            <!-- Box 3: Students -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($usersCount) ?></h3>
                    <p>Users</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Side: Rotating Images -->
    <div class="hero-right">
        <div class="hero-img-grid">
            <div class="hero-img-box img-box-1" id="heroBox1"></div>
            <div class="hero-img-box img-box-2" id="heroBox2"></div>
            <div class="hero-img-box img-box-3" id="heroBox3"></div>
            <div class="hero-img-box img-box-4" id="heroBox4"></div>
        </div>
    </div>

</section>

<!-- Hero Section add the JS -->
<script>
    // Real, current event posters from the database (with a static fallback
    // built in includes/hero.php), so the rotating hero grid always reflects
    // what's actually live in the system.
    window.heroEventImages = <?= json_encode($heroEventImages) ?>;
</script>
<script src="assets/js/hero.js"></script>