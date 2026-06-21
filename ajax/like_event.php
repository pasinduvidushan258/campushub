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

$count = (int) $pdo->query("SELECT COUNT(*) FROM event_likes WHERE event_id = $event_id")->fetchColumn();
echo json_encode(['success' => true, 'action' => $action, 'new_count' => $count]);
?>