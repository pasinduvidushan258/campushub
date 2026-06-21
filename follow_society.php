<?php
// follow_society.php
session_start();
require_once 'config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to follow societies.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$society_id = (int) ($_POST['society_id'] ?? 0);

if (!$society_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid society.']);
    exit();
}

// Block following only the society the user is CURRENTLY acting as (active
// society via switch_role.php) — not every society they happen to administer.
$activeMode      = $_SESSION['active_mode'] ?? 'user';
$activeSocietyId = (int) ($_SESSION['active_society_id'] ?? 0);
if ($activeMode === 'society' && $activeSocietyId === $society_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot follow your own society.']);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM society_followers WHERE user_id = ? AND society_id = ?");
$stmt->execute([$user_id, $society_id]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("DELETE FROM society_followers WHERE id = ?");
    $stmt->execute([$existing['id']]);
    $following = false;
} else {
    $stmt = $pdo->prepare("INSERT INTO society_followers (society_id, user_id) VALUES (?, ?)");
    $stmt->execute([$society_id, $user_id]);
    $following = true;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM society_followers WHERE society_id = ?");
$countStmt->execute([$society_id]);
$followers = (int) $countStmt->fetchColumn();

echo json_encode(['success' => true, 'following' => $following, 'followers' => $followers]);
exit();
?>