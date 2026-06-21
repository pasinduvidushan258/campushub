<?php
// update_society_info.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'includes/function.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$society_id = (int) ($_POST['society_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM societies WHERE id = ? AND admin_id = ?");
$stmt->execute([$society_id, getUserId()]);
if (!$stmt->fetch()) {
    header("Location: societies.php");
    exit();
}

$faculty     = trim($_POST['faculty'] ?? '');
$description = trim($_POST['description'] ?? '');
$vision      = trim($_POST['vision'] ?? '');
$mission     = trim($_POST['mission'] ?? '');
$address     = trim($_POST['address'] ?? '');
$email_1     = trim($_POST['email_1'] ?? '');
$email_2     = trim($_POST['email_2'] ?? '');
$contact     = trim($_POST['contact_number'] ?? '');
$website     = trim($_POST['website_url'] ?? '');
$facebook    = trim($_POST['facebook_url'] ?? '');
$instagram   = trim($_POST['instagram_link'] ?? '');
$founded     = trim($_POST['founded_date'] ?? '');

$update = $pdo->prepare("
    UPDATE societies SET
        faculty = ?, description = ?, vision = ?, mission = ?, address = ?,
        email_1 = ?, email_2 = ?, contact_number = ?, website_url = ?,
        facebook_url = ?, instagram_link = ?, founded_date = ?
    WHERE id = ? AND admin_id = ?
");
$update->execute([
    $faculty, $description, $vision, $mission, $address,
    $email_1, $email_2, $contact, $website ?: null,
    $facebook ?: null, $instagram ?: null, $founded ?: null,
    $society_id, getUserId()
]);

header("Location: society_dashboard.php?id=" . $society_id . "&success=info_updated");
exit();
?>
