<?php
// update_society_photo.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$society_id = (int) ($_POST['society_id'] ?? 0);
$photo_type = $_POST['photo_type'] ?? ''; // 'avatar' (logo) or 'cover'

// Make sure the logged-in user actually owns/manages this society
$stmt = $pdo->prepare("SELECT id FROM societies WHERE id = ? AND admin_id = ?");
$stmt->execute([$society_id, $_SESSION['user_id']]);
$society = $stmt->fetch();

if (!$society) {
    header("Location: society_dashboard.php?id=" . $society_id . "&error=unauthorized");
    exit();
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

    $upload_dir = 'assets/images/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $file_name = time() . '_society_' . $photo_type . '_' . $society_id . '.' . $file_extension;
    $target_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
        if ($photo_type === 'cover') {
            $stmt = $pdo->prepare("UPDATE societies SET cover_path = ? WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE societies SET logo_path = ? WHERE id = ?");
        }
        $stmt->execute([$file_name, $society_id]);
    }
}

header("Location: society_dashboard.php?id=" . $society_id . "&success=photo_updated");
exit();
?>
