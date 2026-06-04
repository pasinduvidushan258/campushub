<?php
session_start();
require 'config/database.php';

// If the user is not logged in, redirect to the login page immediately.
// isset() checks whether $_SESSION['user_id'] was set during login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Read which mode to switch to ('society' or 'user') and the target society ID.
// The ?? operator provides a safe default if the GET parameter is missing.
$type = $_GET['type'] ?? 'user';
$id   = $_GET['id']   ?? 0;

if ($type === 'society') {
    // Verify that the logged-in user is the admin of the requested society
    // AND that the society has been verified before granting society mode.
    $stmt = $pdo->prepare("SELECT id, society_name FROM societies WHERE id = ? AND admin_id = ? AND status = 'verified'");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $society = $stmt->fetch();

    if ($society) {
        // The user is a verified admin. Switch the session context to society mode
        // so that subsequent pages know to render the society dashboard.
        $_SESSION['active_mode']         = 'society';
        $_SESSION['active_society_id']   = $society['id'];
        $_SESSION['active_society_name'] = $society['society_name'];
    }
} else {
    // Switch back to normal user mode.
    // Clearing the society session keys ensures no society context bleeds through.
    $_SESSION['active_mode'] = 'user';
    unset($_SESSION['active_society_id']);
    unset($_SESSION['active_society_name']);
}

// Send the user back to the page they came from (HTTP_REFERER).
// If the referer header is missing (e.g. direct URL access), fall back to index.php.
$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: " . $redirect_url);
exit();
?>