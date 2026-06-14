<?php
require_once 'config/database.php';
require_once 'includes/function.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$event_id = $_POST['event_id'] ?? 0;
$user_id  = getUserId();

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid event']);
    exit;
}

$check = $pdo->prepare("SELECT id FROM event_likes WHERE user_id = ? AND event_id = ?");
$check->execute([$user_id, $event_id]);
$exists = (bool) $check->fetchColumn();

if ($exists) {
    $pdo->prepare("DELETE FROM event_likes WHERE user_id = ? AND event_id = ?")->execute([$user_id, $event_id]);
    $action = 'unliked';
} else {
    $pdo->prepare("INSERT INTO event_likes (user_id, event_id) VALUES (?, ?)")->execute([$user_id, $event_id]);
    $action = 'liked';
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_likes WHERE event_id = ?");
$count_stmt->execute([$event_id]);
$count = (int) $count_stmt->fetchColumn();

echo json_encode(['success' => true, 'action' => $action, 'new_count' => $count]);
?>