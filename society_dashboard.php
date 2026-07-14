<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'config/app.php';
require_once 'includes/function.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$society_id = (int) ($_GET['id'] ?? ($_SESSION['active_society_id'] ?? 0));

$stmt = $pdo->prepare('SELECT * FROM societies WHERE id = ?');
$stmt->execute([$society_id]);
$society = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$society) {
    header('Location: societies.php');
    exit();
}

if ((int) $society['admin_id'] !== (int) getUserId()) {
    header('Location: society_profile.php?id=' . $society_id);
    exit();
}

$name = htmlspecialchars($society['society_name']);
$faculty = htmlspecialchars($society['faculty'] ?? '');
$description = htmlspecialchars($society['description'] ?? 'No description added yet.');
$vision = htmlspecialchars($society['vision'] ?? '');
$mission = htmlspecialchars($society['mission'] ?? '');
$address = htmlspecialchars($society['address'] ?? '');
$email1 = htmlspecialchars($society['email_1'] ?? '');
$email2 = htmlspecialchars($society['email_2'] ?? '');
$contact = htmlspecialchars($society['contact_number'] ?? '');
$website = htmlspecialchars($society['website_url'] ?? '');
$facebook = htmlspecialchars($society['facebook_url'] ?? '');
$instagram = htmlspecialchars($society['instagram_link'] ?? '');
$foundedSource = $society['founded_date'] ?? '';
if (empty($foundedSource) && !empty($society['created_at'])) {
    $foundedSource = date('Y-m-d', strtotime($society['created_at']));
}
$founded = !empty($foundedSource) ? date('F Y', strtotime($foundedSource)) : 'Not specified';

$has_logo = !empty($society['logo_path']) && file_exists('assets/images/uploads/' . $society['logo_path']);
$has_cover = !empty($society['cover_path']) && file_exists('assets/images/uploads/' . $society['cover_path']);
$logo_url = $has_logo ? 'assets/images/uploads/' . htmlspecialchars($society['logo_path']) : '';
$cover_url = $has_cover ? 'assets/images/uploads/' . htmlspecialchars($society['cover_path']) : '';

$eventStmt = $pdo->prepare("SELECT e.*,
    (SELECT COUNT(*) FROM event_likes WHERE event_id = e.id) AS likes_count,
    (SELECT COUNT(*) FROM saved_events WHERE event_id = e.id) AS saves_count
    FROM events e
    WHERE e.society_id = ?
    ORDER BY e.event_date DESC, e.start_time DESC");
$eventStmt->execute([$society_id]);
$events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

$upcomingCount = 0;
$ongoingCount = 0;
$completedCount = 0;
foreach ($events as $event) {
    $status = strtolower((string) ($event['status'] ?? ''));
    if ($status === 'upcoming') {
        $upcomingCount++;
    } elseif ($status === 'ongoing') {
        $ongoingCount++;
    } elseif ($status === 'completed') {
        $completedCount++;
    }
}

$followerStmt = $pdo->prepare('SELECT u.id, u.fullname, u.avatar_url
    FROM society_followers sf
    JOIN users u ON u.id = sf.user_id
    WHERE sf.society_id = ?
    ORDER BY sf.created_at DESC');
$followerStmt->execute([$society_id]);
$followers = $followerStmt->fetchAll(PDO::FETCH_ASSOC);
$followerCount = count($followers);

$memberStmt = $pdo->prepare('SELECT COUNT(*) FROM society_managers WHERE society_id = ?');
$memberStmt->execute([$society_id]);
$memberCount = (int) $memberStmt->fetchColumn();

$adminEmail = '';
$adminEmailStmt = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
$adminEmailStmt->execute([(int) $society['admin_id']]);
$adminEmailRaw = trim((string) $adminEmailStmt->fetchColumn());
if ($adminEmailRaw !== '') {
    $adminEmail = htmlspecialchars($adminEmailRaw);
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/event.css">
<link rel="stylesheet" href="assets/css/society.css">
<link rel="stylesheet" href="assets/css/add-event-modal.css">

<div class="soc-container soc-dashboard-page">

    <div class="soc-header">
        <form action="update_society_photo.php" method="POST" enctype="multipart/form-data" id="coverForm" style="display:none;">
            <input type="hidden" name="society_id" value="<?= $society_id ?>">
            <input type="hidden" name="photo_type" value="cover">
            <input type="file" id="coverUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('coverForm').submit();">
        </form>

        <div class="soc-cover" <?= $has_cover ? "style=\"background-image:url('$cover_url')\"" : '' ?>>
            <?php if (!$has_cover): ?><i class="fas fa-image"></i><?php endif; ?>
            <button class="edit-cover-btn" onclick="document.getElementById('coverUpload').click();"><i class="fas fa-camera"></i> Edit Cover</button>
        </div>

        <div class="soc-info-section">
            <form action="update_society_photo.php" method="POST" enctype="multipart/form-data" id="logoForm" style="display:none;">
                <input type="hidden" name="society_id" value="<?= $society_id ?>">
                <input type="hidden" name="photo_type" value="avatar">
                <input type="file" id="logoUpload" name="photo" accept="image/png, image/jpeg, image/jpg" onchange="document.getElementById('logoForm').submit();">
            </form>

            <div class="soc-logo-wrapper">
                <?php if ($has_logo): ?>
                    <img src="<?= $logo_url ?>" alt="<?= $name ?>" class="soc-logo-img">
                <?php else: ?>
                    <div class="soc-logo-img"><i class="fas fa-landmark"></i></div>
                <?php endif; ?>
                <button class="edit-logo-btn" onclick="document.getElementById('logoUpload').click();"><i class="fas fa-camera"></i></button>
            </div>

            <div class="soc-details">
                <h1 class="soc-name">
                    <?= $name ?>
                    <?php if ($society['status'] === 'verified'): ?>
                        <i class="fas fa-check-circle soc-verified-badge" title="Verified Society"></i>
                    <?php endif; ?>
                </h1>
                <p class="soc-tagline"><?= $faculty ? $faculty : 'Welcome to your management dashboard' ?></p>
                <div class="soc-meta">
                    <span><i class="fas fa-users"></i> <?= $followerCount ?> follower<?= $followerCount === 1 ? '' : 's' ?></span>
                    <span><i class="fas fa-calendar"></i> <?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?></span>
                    <span><i class="fas fa-user-friends"></i> <?= $memberCount ?> manager<?= $memberCount === 1 ? '' : 's' ?></span>
                    <span><i class="fas fa-shield-alt"></i> <?= ucfirst($society['status']) ?></span>
                </div>
            </div>

            <div class="soc-actions">
                <button class="btn-primary" onclick="openAddEventModal()"><i class="fas fa-plus"></i> Add Event</button>
                <button class="btn-secondary" onclick="openEditModal()"><i class="fas fa-cog"></i> Manage</button>
            </div>
        </div>
    </div>

    <div class="dashboard-stats-grid">
        <div class="dashboard-stat-card">
            <i class="fas fa-calendar-check"></i>
            <div>
                <span>Total Events</span>
                <strong><?= count($events) ?></strong>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <i class="fas fa-hourglass-half"></i>
            <div>
                <span>Upcoming</span>
                <strong><?= $upcomingCount ?></strong>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <i class="fas fa-spinner"></i>
            <div>
                <span>Ongoing</span>
                <strong><?= $ongoingCount ?></strong>
            </div>
        </div>
        <div class="dashboard-stat-card">
            <i class="fas fa-flag-checkered"></i>
            <div>
                <span>Completed</span>
                <strong><?= $completedCount ?></strong>
            </div>
        </div>
    </div>

    <div class="soc-tabs-container">
        <button class="soc-tab active" onclick="openSocTab(event, 'about')">About</button>
        <button class="soc-tab" onclick="openSocTab(event, 'photos')">Photos</button>
        <button class="soc-tab" onclick="openSocTab(event, 'events')">Events</button>
        <button class="soc-tab" onclick="openSocTab(event, 'followers')">Followers</button>
    </div>

    <div class="soc-content">

        <div id="about" class="tab-pane active">
            <div class="dashboard-about-grid">
                <div class="info-card dashboard-primary-card">
                    <h3><i class="fas fa-address-card"></i> Contact &amp; Details</h3>
                    <ul class="info-list">
                        <li><strong>Faculty:</strong> <?= $faculty ?: 'Not specified' ?></li>
                        <li><strong>Founded:</strong> <?= $founded ?></li>
                        <li><strong>Address:</strong> <?= $address ?: 'Not specified' ?></li>
                        <?php if ($adminEmail): ?><li><strong>Admin Email:</strong> <?= $adminEmail ?></li><?php endif; ?>
                        <li><strong>Primary Email:</strong> <?= $email1 ?: 'Not specified' ?></li>
                        <li><strong>Secondary Email:</strong> <?= $email2 ?: 'Not specified' ?></li>
                        <?php if ($contact): ?><li><strong>Contact No:</strong> <?= $contact ?></li><?php endif; ?>
                        <?php if ($website): ?><li><strong>Website:</strong> <a href="<?= $website ?>" target="_blank" class="soc-inline-link"><?= $website ?></a></li><?php endif; ?>
                        <?php if ($facebook): ?><li><strong>Facebook:</strong> <a href="<?= $facebook ?>" target="_blank" class="soc-inline-link">View page</a></li><?php endif; ?>
                        <?php if ($instagram): ?><li><strong>Instagram:</strong> <a href="<?= $instagram ?>" target="_blank" class="soc-inline-link">View page</a></li><?php endif; ?>
                    </ul>
                </div>

                <div class="dashboard-secondary-stack">
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> About</h3>
                        <p class="soc-prose"><?= nl2br($description) ?></p>
                    </div>

                    <?php if ($vision || $mission): ?>
                    <div class="info-card">
                        <h3><i class="fas fa-bullseye"></i> Vision &amp; Mission</h3>
                        <ul class="info-list">
                            <?php if ($vision): ?><li><strong>Vision:</strong> <?= nl2br($vision) ?></li><?php endif; ?>
                            <?php if ($mission): ?><li><strong>Mission:</strong> <?= nl2br($mission) ?></li><?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="photos" class="tab-pane">
            <?php if ($has_logo || $has_cover): ?>
                <div class="dashboard-photo-grid">
                    <?php if ($has_cover): ?>
                        <article class="dashboard-photo-card">
                            <img src="<?= $cover_url ?>" alt="Cover">
                            <span>Cover Photo</span>
                        </article>
                    <?php endif; ?>
                    <?php if ($has_logo): ?>
                        <article class="dashboard-photo-card">
                            <img src="<?= $logo_url ?>" alt="Logo">
                            <span>Society Logo</span>
                        </article>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="soc-empty-state">
                    <i class="fas fa-images"></i>
                    <h3>No photos yet</h3>
                    <p>Upload a cover photo and logo to showcase your society.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="events" class="tab-pane">
            <?php if (!empty($events)): ?>
                <div class="events-grid dashboard-events-grid">
                    <?php foreach ($events as $ev): ?>
                        <?php
                            $eventPoster = !empty($ev['poster_path']) && file_exists('assets/images/events/' . $ev['poster_path'])
                                ? 'assets/images/events/' . htmlspecialchars($ev['poster_path'])
                                : '';

                            $statusValue = strtolower((string) ($ev['status'] ?? 'upcoming'));
                            if (!in_array($statusValue, ['upcoming', 'ongoing', 'completed'], true)) {
                                $statusValue = 'upcoming';
                            }
                        ?>
                        <article class="event-card">
                            <div class="event-poster-wrap">
                                <?php if ($eventPoster): ?>
                                    <img src="<?= $eventPoster ?>" alt="<?= htmlspecialchars($ev['title']) ?>">
                                <?php else: ?>
                                    <div class="event-poster-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>No Flyer</span>
                                    </div>
                                <?php endif; ?>
                                <span class="event-status-badge badge-<?= htmlspecialchars($statusValue) ?>"><?= ucfirst($statusValue) ?></span>
                                <span class="event-category-chip"><?= htmlspecialchars($ev['category'] ?? 'General') ?></span>
                            </div>

                            <div class="event-body">
                                <h3 class="event-title"><?= htmlspecialchars($ev['title']) ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-row"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($ev['event_date'])) ?> at <?= date('h:i A', strtotime($ev['start_time'])) ?></div>
                                    <div class="event-meta-row"><i class="fas fa-map-marker-alt"></i> <?= !empty($ev['venue']) ? htmlspecialchars($ev['venue']) : 'Venue to be announced' ?></div>
                                    <div class="event-meta-row"><i class="fas fa-heart"></i> <?= (int) $ev['likes_count'] ?> likes <span class="event-row-dot"></span> <i class="fas fa-bookmark"></i> <?= (int) $ev['saves_count'] ?> saves</div>
                                </div>

                                <div class="event-footer">
                                    <a href="event_details.php?id=<?= (int) $ev['id'] ?>" class="action-btn-details">View Details</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="soc-empty-state">
                    <i class="fas fa-calendar-xmark"></i>
                    <h3>No events yet</h3>
                    <p>Events you create will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="followers" class="tab-pane">
            <?php if (!empty($followers)): ?>
                <div class="dashboard-followers-grid">
                    <?php foreach ($followers as $f): ?>
                        <?php
                            $favatar = !empty($f['avatar_url']) && $f['avatar_url'] !== 'assets/images/default_avatar.png'
                                ? app_url($f['avatar_url']) : '';
                        ?>
                        <article class="dashboard-follower-card">
                            <?php if ($favatar): ?>
                                <img src="<?= $favatar ?>" class="soc-follower-avatar" alt="<?= htmlspecialchars($f['fullname']) ?>">
                            <?php else: ?>
                                <div class="soc-follower-avatar dashboard-fallback-avatar"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <h4><?= htmlspecialchars($f['fullname']) ?></h4>
                            <p>Follower</p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="soc-empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No followers yet</h3>
                    <p>Followers will show up here once students follow your society.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<div id="addEventModal" class="ae-modal-overlay">
    <div class="ae-modal">
        <div class="ae-modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Add New Event</h3>
            <button type="button" class="ae-modal-close" onclick="closeAddEventModal()"><i class="fas fa-times"></i></button>
        </div>

        <form id="addEventForm" enctype="multipart/form-data">
            <div class="ae-modal-body">

                <div id="aeFormMessage" class="ae-form-message"></div>

                <input type="hidden" name="society_id" value="<?= $society_id ?>">

                <div class="ae-field">
                    <label for="ae_title">Event Title <span class="ae-required">*</span></label>
                    <input type="text" id="ae_title" name="title" maxlength="255" required placeholder="e.g. AI Workshop 2026">
                </div>

                <div class="ae-row">
                    <div class="ae-field">
                        <label for="ae_category">Category <span class="ae-required">*</span></label>
                        <select id="ae_category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Competition">Competition</option>
                            <option value="Sports">Sports</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Music">Music</option>
                            <option value="Technology">Technology</option>
                            <option value="Career">Career</option>
                            <option value="Volunteer">Volunteer</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="ae-field">
                        <label for="ae_event_mode">Event Mode</label>
                        <select id="ae_event_mode" name="event_mode">
                            <option value="physical">Physical</option>
                            <option value="online">Online</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>

                <div class="ae-field">
                    <label for="ae_venue">Venue</label>
                    <input type="text" id="ae_venue" name="venue" maxlength="255" placeholder="e.g. Main Auditorium">
                </div>

                <div class="ae-row">
                    <div class="ae-field">
                        <label for="ae_event_date">Event Date <span class="ae-required">*</span></label>
                        <input type="date" id="ae_event_date" name="event_date" required>
                    </div>

                    <div class="ae-field">
                        <label for="ae_start_time">Start Time <span class="ae-required">*</span></label>
                        <input type="time" id="ae_start_time" name="start_time" required>
                    </div>

                    <div class="ae-field">
                        <label for="ae_end_time">End Time</label>
                        <input type="time" id="ae_end_time" name="end_time">
                    </div>
                </div>

                <div class="ae-field">
                    <label for="ae_registration_link">Registration Link</label>
                    <input type="url" id="ae_registration_link" name="registration_link" placeholder="https://forms.gle/...">
                </div>

                <div class="ae-field">
                    <label for="ae_description">Description</label>
                    <textarea id="ae_description" name="description" rows="4" placeholder="What is this event about?"></textarea>
                </div>

                <div class="ae-field">
                    <label for="ae_poster">Event Poster</label>
                    <input type="file" id="ae_poster" name="poster" accept="image/png, image/jpeg, image/jpg, image/webp">
                    <span class="ae-hint">JPG, PNG or WEBP. Optional.</span>
                </div>

            </div>

            <div class="ae-modal-footer">
                <button type="button" class="btn-secondary" onclick="closeAddEventModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="aeSubmitBtn"><i class="fas fa-plus"></i> Add Event</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSocTab(evt, tabName) {
    document.querySelectorAll('.tab-pane').forEach(function (el) {
        el.classList.remove('active');
    });
    document.querySelectorAll('.soc-tab').forEach(function (el) {
        el.classList.remove('active');
    });
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}

function openEditModal() {
    window.location.href = 'edit_society.php?id=<?= $society_id ?>';
}

const addEventModal = document.getElementById('addEventModal');
const addEventForm = document.getElementById('addEventForm');
const aeFormMessage = document.getElementById('aeFormMessage');
const aeSubmitBtn = document.getElementById('aeSubmitBtn');

function openAddEventModal() {
    addEventModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAddEventModal() {
    addEventModal.classList.remove('active');
    document.body.style.overflow = '';
    addEventForm.reset();
    aeFormMessage.style.display = 'none';
    aeFormMessage.className = 'ae-form-message';
}

addEventModal.addEventListener('click', function (e) {
    if (e.target === addEventModal) {
        closeAddEventModal();
    }
});

addEventForm.addEventListener('submit', function (e) {
    e.preventDefault();

    aeFormMessage.style.display = 'none';
    aeFormMessage.className = 'ae-form-message';

    aeSubmitBtn.disabled = true;
    aeSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

    const formData = new FormData(addEventForm);

    fetch('create_event.php', {
        method: 'POST',
        body: formData
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        aeSubmitBtn.disabled = false;
        aeSubmitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Event';

        if (data.success) {
            aeFormMessage.textContent = data.message || 'Event created successfully.';
            aeFormMessage.className = 'ae-form-message ae-success';
            aeFormMessage.style.display = 'block';
            setTimeout(function () {
                window.location.reload();
            }, 900);
        } else {
            aeFormMessage.textContent = data.message || 'Something went wrong. Please try again.';
            aeFormMessage.className = 'ae-form-message ae-error';
            aeFormMessage.style.display = 'block';
        }
    })
    .catch(function () {
        aeSubmitBtn.disabled = false;
        aeSubmitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Event';
        aeFormMessage.textContent = 'Network error. Please try again.';
        aeFormMessage.className = 'ae-form-message ae-error';
        aeFormMessage.style.display = 'block';
    });
});
</script>
