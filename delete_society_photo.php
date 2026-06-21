<?php
// delete_society_photo.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'includes/function.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$society_id = (int) ($_POST['society_id'] ?? 0);
$photo_type = $_POST['photo_type'] ?? ''; // 'avatar' or 'cover'

$stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ? AND admin_id = ?");
$stmt->execute([$society_id, getUserId()]);
$society = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$society) {
    header("Location: societies.php");
    exit();
}

$column = $photo_type === 'cover' ? 'cover_path' : 'logo_path';

if (!empty($society[$column])) {
    $filePath = 'assets/images/uploads/' . $society[$column];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
    $update = $pdo->prepare("UPDATE societies SET $column = NULL WHERE id = ? AND admin_id = ?");
    $update->execute([$society_id, getUserId()]);
}

header("Location: society_dashboard.php?id=" . $society_id . "&success=photo_deleted");
exit();
?>
