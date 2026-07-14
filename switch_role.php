<?php
// Start output buffering to prevent headers from being sent prematurely
ob_start();
session_start();
require 'config/database.php';
require_once 'config/app.php';
require_once 'includes/notification_helpers.php';

ob_clean(); // Clear any unnecessary output
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get data from POST request
$type = $_POST['type'] ?? 'user';
$id   = $_POST['id']   ?? 0;

if ($type === 'society') {
    $stmt = $pdo->prepare("SELECT id, society_name FROM societies WHERE id = ? AND admin_id = ? AND status = 'verified'");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $society = $stmt->fetch();

    if ($society) {
        $_SESSION['active_mode']         = 'society';
        $_SESSION['active_society_id']   = $society['id'];
        $_SESSION['active_society_name'] = $society['society_name'];

        campushub_notify_user($pdo, [
            'recipient_user_id' => (int) $_SESSION['user_id'],
            'actor_society_id' => (int) $society['id'],
            'type' => 'role_switched',
            'title' => 'Role switched',
            'message' => 'You switched to society mode: ' . (string) $society['society_name'] . '.',
            'entity_type' => 'society',
            'entity_id' => (int) $society['id'],
            'link_url' => 'society_dashboard.php',
            'dedupe_key' => 'role-switch:' . (int) $_SESSION['user_id'] . ':society:' . (int) $society['id'] . ':' . date('YmdHi'),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid society']);
        exit();
    }
} else {
    $_SESSION['active_mode'] = 'user';
    unset($_SESSION['active_society_id']);
    unset($_SESSION['active_society_name']);

    campushub_notify_user($pdo, [
        'recipient_user_id' => (int) $_SESSION['user_id'],
        'actor_user_id' => (int) $_SESSION['user_id'],
        'type' => 'role_switched',
        'title' => 'Role switched',
        'message' => 'You switched back to personal profile mode.',
        'entity_type' => 'user',
        'entity_id' => (int) $_SESSION['user_id'],
        'link_url' => 'my_profile.php',
        'dedupe_key' => 'role-switch:' . (int) $_SESSION['user_id'] . ':user:' . date('YmdHi'),
    ]);
}

// Always redirect to the home page
$home_page_url = app_url('index.php'); 

echo json_encode(['success' => true, 'redirect_url' => $home_page_url]);
exit();
?>