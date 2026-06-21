<?php
// create_event.php
// Handles creation of a new event from the society dashboard "Add Event" modal.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/function.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$society_id = (int) ($_POST['society_id'] ?? 0);

// Confirm the logged-in user actually owns/manages this society.
$stmt = $pdo->prepare("SELECT id FROM societies WHERE id = ? AND admin_id = ?");
$stmt->execute([$society_id, getUserId()]);
$society = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$society) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$title              = trim($_POST['title'] ?? '');
$description        = trim($_POST['description'] ?? '');
$category           = trim($_POST['category'] ?? '');
$venue              = trim($_POST['venue'] ?? '');
$event_date         = trim($_POST['event_date'] ?? '');
$start_time         = trim($_POST['start_time'] ?? '');
$end_time           = trim($_POST['end_time'] ?? '');
$registration_link  = trim($_POST['registration_link'] ?? '');
$event_mode         = trim($_POST['event_mode'] ?? 'physical');

$allowed_categories = ['Workshop','Seminar','Competition','Sports','Cultural','Music','Technology','Career','Volunteer','Other'];
$allowed_modes       = ['physical','online','hybrid'];

$errors = [];

if ($title === '') {
    $errors[] = 'Title is required.';
}
if (!in_array($category, $allowed_categories, true)) {
    $errors[] = 'Please choose a valid category.';
}
if ($event_date === '' || !strtotime($event_date)) {
    $errors[] = 'A valid event date is required.';
}
if ($start_time === '' || !strtotime($start_time)) {
    $errors[] = 'A valid start time is required.';
}
if ($end_time !== '' && !strtotime($end_time)) {
    $errors[] = 'End time is invalid.';
}
if (!in_array($event_mode, $allowed_modes, true)) {
    $event_mode = 'physical';
}
if ($registration_link !== '' && !filter_var($registration_link, FILTER_VALIDATE_URL)) {
    $errors[] = 'Registration link must be a valid URL.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Handle optional poster upload.
$poster_filename = null;

if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {

    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext, true)) {
        echo json_encode(['success' => false, 'message' => 'Poster must be a JPG, PNG, or WEBP image.']);
        exit;
    }

    $upload_dir = 'assets/images/events/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $poster_filename = time() . '_event_' . $society_id . '.' . $ext;
    $target_path = $upload_dir . $poster_filename;

    if (!move_uploaded_file($_FILES['poster']['tmp_name'], $target_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload poster image.']);
        exit;
    }
}

$insert = $pdo->prepare("
    INSERT INTO events
        (society_id, title, description, category, venue, event_date, start_time, end_time, poster_path, registration_link, event_mode, status)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')
");

$insert->execute([
    $society_id,
    $title,
    $description !== '' ? $description : null,
    $category,
    $venue !== '' ? $venue : null,
    $event_date,
    $start_time,
    $end_time !== '' ? $end_time : null,
    $poster_filename,
    $registration_link !== '' ? $registration_link : null,
    $event_mode,
]);

$new_event_id = $pdo->lastInsertId();

echo json_encode([
    'success'  => true,
    'message'  => 'Event created successfully.',
    'event_id' => $new_event_id,
]);
exit;
