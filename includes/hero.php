<?php
// hero.php
// Pulls the most recent upcoming/ongoing events that have a poster image,
// so the homepage hero grid always reflects real, current events instead
// of a hardcoded placeholder list.
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

$heroEventImages = [];

try {
    $heroStmt = $pdo->prepare("
        SELECT poster_path
        FROM events
        WHERE poster_path IS NOT NULL
          AND poster_path != ''
          AND status IN ('upcoming', 'ongoing')
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
    error_log('[hero.php] Failed to load event posters: ' . $e->getMessage());
}

// Fallback so the hero section never renders empty/broken boxes
// when there aren't enough events with posters yet (e.g. fresh install).
$heroFallbackImages = [
    'assets/images/events/event1.jpeg',
    'assets/images/events/event2.jpeg',
    'assets/images/events/event3.jpeg',
    'assets/images/events/event4.jpeg',
    'assets/images/events/event5.jpeg',
    'assets/images/events/event6.jpeg',
];

while (count($heroEventImages) < 4 && !empty($heroFallbackImages)) {
    $heroEventImages[] = array_shift($heroFallbackImages);
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
                    <h3>50<span>+</span></h3>
                    <p>Societies</p>
                </div>
            </div>

            <!-- Box 2: Events -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>200<span>+</span></h3>
                    <p>Events</p>
                </div>
            </div>

            <!-- Box 3: Students -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3>5k<span>+</span></h3>
                    <p>Students</p>
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