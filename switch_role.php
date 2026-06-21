<?php
// Start output buffering to prevent headers from being sent prematurely
ob_start();
session_start();
require 'config/database.php';

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
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid society']);
        exit();
    }
} else {
    $_SESSION['active_mode'] = 'user';
    unset($_SESSION['active_society_id']);
    unset($_SESSION['active_society_name']);
}

// Always redirect to the home page
$home_page_url = '/campushub/index.php'; 

echo json_encode(['success' => true, 'redirect_url' => $home_page_url]);
exit();
?>