<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/security_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$extra_stylesheets = ['assets/css/settings.css'];

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT email, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit();
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings & Privacy - CampusHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="settings-page">
    <!-- All content removed -->
</div>

</body>
</html>