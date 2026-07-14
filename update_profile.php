<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_profile.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$fullname = trim((string) ($_POST['fullname'] ?? ''));
$tagline = trim((string) ($_POST['tagline'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$school = trim((string) ($_POST['school'] ?? ''));
$qualifications = trim((string) ($_POST['qualifications'] ?? ''));

if ($fullname === '') {
    header('Location: my_profile.php?error=missing_name');
    exit();
}

$tagline = $tagline !== '' ? $tagline : null;
$location = $location !== '' ? $location : null;
$school = $school !== '' ? $school : null;
$qualifications = $qualifications !== '' ? $qualifications : null;

if (!isset($pdo)) {
    header('Location: my_profile.php?error=db');
    exit();
}

$updateStmt = $pdo->prepare(
    'UPDATE users
    SET fullname = ?,
        tagline = ?,
        location = ?,
        school = ?,
        qualifications = ?
    WHERE id = ?'
);

$updateStmt->execute([
    $fullname,
    $tagline,
    $location,
    $school,
    $qualifications,
    $user_id
]);

$_SESSION['fullname'] = $fullname;

header('Location: my_profile.php?success=profile_updated');
exit();
