<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/function.php';
require_once 'includes/notification_helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userId = (int) getUserId();
$eventId = (int) ($_POST['event_id'] ?? 0);

if ($eventId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid event id.']);
    exit;
}

$status = trim((string) ($_POST['status'] ?? ''));
$eventDate = trim((string) ($_POST['event_date'] ?? ''));
$startTime = trim((string) ($_POST['start_time'] ?? ''));
$endTime = trim((string) ($_POST['end_time'] ?? ''));
$venue = trim((string) ($_POST['venue'] ?? ''));
$registrationClosingSoon = !empty($_POST['registration_closing_soon']);
$fullCapacity = !empty($_POST['full_capacity']);

$allowedStatus = ['upcoming', 'ongoing', 'completed', 'cancelled'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

try {
    $eventStmt = $pdo->prepare("SELECT e.*, s.admin_id, s.society_name, s.id AS society_id FROM events e INNER JOIN societies s ON s.id = e.society_id WHERE e.id = ? LIMIT 1");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit;
    }

    if ((int) $event['admin_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not allowed.']);
        exit;
    }

    $updates = [];
    $params = [];

    if ($status !== '' && $status !== (string) $event['status']) {
        $updates[] = 'status = ?';
        $params[] = $status;
    }

    if ($eventDate !== '' && $eventDate !== (string) $event['event_date']) {
        $updates[] = 'event_date = ?';
        $params[] = $eventDate;
    }

    if ($startTime !== '' && $startTime !== (string) $event['start_time']) {
        $updates[] = 'start_time = ?';
        $params[] = $startTime;
    }

    if ($endTime !== '' && $endTime !== (string) $event['end_time']) {
        $updates[] = 'end_time = ?';
        $params[] = $endTime;
    }

    if ($venue !== '' && $venue !== (string) $event['venue']) {
        $updates[] = 'venue = ?';
        $params[] = $venue;
    }

    if (!empty($updates)) {
        $params[] = $eventId;
        $sql = 'UPDATE events SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $updStmt = $pdo->prepare($sql);
        $updStmt->execute($params);
    }

    $recipients = [];
    $saveFollowers = $pdo->prepare("SELECT DISTINCT user_id FROM saved_events WHERE event_id = ?");
    $saveFollowers->execute([$eventId]);
    foreach ($saveFollowers->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $recipients[] = (int) ($row['user_id'] ?? 0);
    }

    $socFollowers = $pdo->prepare("SELECT DISTINCT user_id FROM society_followers WHERE society_id = ?");
    $socFollowers->execute([(int) $event['society_id']]);
    foreach ($socFollowers->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $recipients[] = (int) ($row['user_id'] ?? 0);
    }

    $recipients = array_values(array_unique(array_filter($recipients)));

    $timeChanged = ($eventDate !== '' && $eventDate !== (string) $event['event_date'])
        || ($startTime !== '' && $startTime !== (string) $event['start_time'])
        || ($endTime !== '' && $endTime !== (string) $event['end_time']);
    $locationChanged = ($venue !== '' && $venue !== (string) $event['venue']);

    if ($status === 'cancelled') {
        campushub_notify_many($pdo, $recipients, [
            'actor_user_id' => $userId,
            'actor_society_id' => (int) $event['society_id'],
            'type' => 'event_cancelled',
            'title' => 'Event cancelled',
            'message' => (string) ($event['title'] ?? 'An event') . ' has been cancelled.',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-cancelled:' . $eventId . ':' . date('YmdHi'),
        ]);
    }

    if ($timeChanged) {
        campushub_notify_many($pdo, $recipients, [
            'actor_user_id' => $userId,
            'actor_society_id' => (int) $event['society_id'],
            'type' => 'event_time_changed',
            'title' => 'Event time changed',
            'message' => (string) ($event['title'] ?? 'An event') . ' has updated schedule details.',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-time-change:' . $eventId . ':' . date('YmdHi'),
        ]);
    }

    if ($locationChanged) {
        campushub_notify_many($pdo, $recipients, [
            'actor_user_id' => $userId,
            'actor_society_id' => (int) $event['society_id'],
            'type' => 'event_location_changed',
            'title' => 'Event location changed',
            'message' => (string) ($event['title'] ?? 'An event') . ' has a venue/location update.',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-location-change:' . $eventId . ':' . date('YmdHi'),
        ]);
    }

    if ($registrationClosingSoon) {
        campushub_notify_many($pdo, $recipients, [
            'actor_user_id' => $userId,
            'actor_society_id' => (int) $event['society_id'],
            'type' => 'event_registration_closing_soon',
            'title' => 'Event registration closing soon',
            'message' => (string) ($event['title'] ?? 'An event') . ' registration is closing soon.',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-registration-closing:' . $eventId . ':' . date('YmdHi'),
        ]);
    }

    if ($fullCapacity) {
        campushub_notify_many($pdo, $recipients, [
            'actor_user_id' => $userId,
            'actor_society_id' => (int) $event['society_id'],
            'type' => 'event_full_capacity',
            'title' => 'Event reached full capacity',
            'message' => (string) ($event['title'] ?? 'An event') . ' has reached full capacity.',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-full-capacity:' . $eventId . ':' . date('YmdHi'),
        ]);
    }

    if ($status === 'completed') {
        campushub_notify_many($pdo, $recipients, [
            'actor_user_id' => $userId,
            'actor_society_id' => (int) $event['society_id'],
            'type' => 'event_feedback_request',
            'title' => 'Event ended, share feedback',
            'message' => (string) ($event['title'] ?? 'An event') . ' has ended. Share your feedback.',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-feedback:' . $eventId . ':' . date('YmdHi'),
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Event updated and notifications sent.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update event right now.']);
}
