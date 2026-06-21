<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function userLikedEvent($user_id, $event_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM event_likes WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    return (bool) $stmt->fetchColumn();
}

function userSavedEvent($user_id, $event_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM saved_events WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    return (bool) $stmt->fetchColumn();
}
?>