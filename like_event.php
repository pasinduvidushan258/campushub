<?php
// like_event.php
// AJAX endpoint: toggles a liked/unliked state for an event for the logged-in user.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/function.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$event_id = (int) ($_POST['event_id'] ?? 0);
$user_id  = (int) getUserId();

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event']);
    exit;
}

try {
    $eventCheck = $pdo->prepare("SELECT id FROM events WHERE id = ?");
    $eventCheck->execute([$event_id]);

    if (!$eventCheck->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit;
    }

    $check = $pdo->prepare("SELECT id FROM event_likes WHERE user_id = ? AND event_id = ?");
    $check->execute([$user_id, $event_id]);
    $exists = (bool) $check->fetchColumn();

    if ($exists) {
        $pdo->prepare("DELETE FROM event_likes WHERE user_id = ? AND event_id = ?")
            ->execute([$user_id, $event_id]);
        $action = 'unliked';
    } else {
        // INSERT IGNORE + the DB's own unique(user_id, event_id) constraint
        // means a double-click or duplicate request can never create two rows.
        $insert = $pdo->prepare("INSERT IGNORE INTO event_likes (user_id, event_id) VALUES (?, ?)");
        $insert->execute([$user_id, $event_id]);
        $action = 'liked';
    }

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_likes WHERE event_id = ?");
    $count_stmt->execute([$event_id]);
    $count = (int) $count_stmt->fetchColumn();

    echo json_encode([
        'success'   => true,
        'action'    => $action,
        'new_count' => $count,
        'liked'     => $action === 'liked',
    ]);

} catch (PDOException $e) {
    error_log('[like_event.php] DB error for user_id=' . $user_id . ', event_id=' . $event_id . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong while liking. Please try again.']);
}
