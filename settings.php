<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$extra_stylesheets = ['assets/css/settings.css'];

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$user_email = $_SESSION['email'] ?? '';

$stmt = $pdo->prepare("SELECT email, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit();
}

$user_email = $current_user['email'] ?? $user_email;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_email') {
        $current_password = $_POST['current_password'] ?? '';
        $new_email = trim($_POST['new_email'] ?? '');
        $confirm_email = trim($_POST['confirm_email'] ?? '');

        if ($current_password === '' || $new_email === '' || $confirm_email === '') {
            $error = 'Please fill in all email fields.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!str_ends_with($new_email, '.lk')) {
            $error = 'Login email must end with .lk.';
        } elseif ($new_email !== $confirm_email) {
            $error = 'The email addresses do not match.';
        } elseif (!password_verify($current_password, $current_user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new_email === $current_user['email']) {
            $error = 'That email is already your current login email.';
        } else {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
            $check_stmt->execute([$new_email, $user_id]);

            if ($check_stmt->fetch()) {
                $error = 'That email is already in use by another account.';
            } else {
                $update_stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update_stmt->execute([$new_email, $user_id]);

                $_SESSION['email'] = $new_email;
                $user_email = $new_email;
                $success = 'Your login email has been updated successfully.';
            }
        }
    }

    if ($action === 'change_password') {
        $current_password = $_POST['password_current'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $error = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'The new passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!password_verify($current_password, $current_user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);

            $success = 'Your password has been updated successfully.';
        }
    }
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
    <div class="settings-hero">
        <div class="settings-kicker">Settings & Privacy</div>
        <h1>Manage your login details</h1>
        <p>Update the email you use to sign in and change your password from one secure place. For safety, both actions require your current password before saving changes.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="settings-alert success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="settings-alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <section class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon"><i class="fas fa-envelope"></i></div>
                <div>
                    <h2>Change Login Email</h2>
                    <p>This becomes the email you use to log in to CampusHub.</p>
                </div>
            </div>

            <form class="settings-form" method="POST" action="settings.php">
                <input type="hidden" name="action" value="change_email">

                <div class="settings-meta">
                    Current login email: <strong style="color:#F8FAFC;"><?php echo htmlspecialchars($user_email); ?></strong>
                </div>

                <div class="settings-field">
                    <label for="current_password_email">Current Password</label>
                    <input class="settings-input" type="password" id="current_password_email" name="current_password" placeholder="Enter your current password" required>
                </div>

                <div class="settings-field">
                    <label for="new_email">New Login Email</label>
                    <input class="settings-input" type="email" id="new_email" name="new_email" placeholder="name@example.lk" required>
                    <div class="field-hint">Use a valid email address ending with <strong>.lk</strong>.</div>
                </div>

                <div class="settings-field">
                    <label for="confirm_email">Confirm New Email</label>
                    <input class="settings-input" type="email" id="confirm_email" name="confirm_email" placeholder="Re-enter the new email" required>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="settings-button primary">Update Login Email</button>
                </div>
            </form>
        </section>

        <section class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon"><i class="fas fa-lock"></i></div>
                <div>
                    <h2>Change Password</h2>
                    <p>Choose a new password for your CampusHub account.</p>
                </div>
            </div>

            <form class="settings-form" method="POST" action="settings.php">
                <input type="hidden" name="action" value="change_password">

                <div class="settings-field">
                    <label for="password_current">Current Password</label>
                    <input class="settings-input" type="password" id="password_current" name="password_current" placeholder="Enter your current password" required>
                </div>

                <div class="settings-field">
                    <label for="new_password">New Password</label>
                    <input class="settings-input" type="password" id="new_password" name="new_password" placeholder="Create a new password" required>
                    <div class="field-hint">Use at least 8 characters for a stronger password.</div>
                </div>

                <div class="settings-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <input class="settings-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter the new password" required>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="settings-button primary">Update Password</button>
                    <a href="my_profile.php" class="settings-button secondary">Back to Profile</a>
                </div>
            </form>
        </section>
    </div>
</div>

</body>
</html>
