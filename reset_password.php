<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$error = '';
$success = '';

$reset_session = $_SESSION['password_reset'] ?? null;

if (!$reset_session || empty($reset_session['email']) || empty($reset_session['code'])) {
    header('Location: forgot_password.php');
    exit();
}

if (!empty($_GET['sent'])) {
    $success = 'A reset code has been sent to your email address.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = trim($_POST['code'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (time() > (int) $reset_session['expires_at']) {
        unset($_SESSION['password_reset']);
        $error = 'The reset code has expired. Please request a new one.';
    } elseif ($entered_code === '' || $new_password === '' || $confirm_password === '') {
        $error = 'Please complete all fields.';
    } elseif ($entered_code !== (string) $reset_session['code']) {
        $error = 'The verification code is incorrect.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'The new passwords do not match.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $pdo->prepare('UPDATE users SET password = ?, otp = NULL WHERE email = ?');
        $update_stmt->execute([$hashed_password, $reset_session['email']]);

        unset($_SESSION['password_reset']);

        header('Location: login.php?reset=success');
        exit();
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-main">
    <div class="auth-box">
        <div class="auth-header">
            <h2>Reset Password</h2>
            <p>Enter the verification code sent to <?php echo htmlspecialchars($reset_session['email']); ?>.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="reset_password.php" method="POST" class="auth-form">
            <div class="input-group">
                <i class="fas fa-shield-alt input-icon"></i>
                <input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" required>
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="new_password" id="resetNewPassword" placeholder="New password" required>
                <i class="fas fa-eye-slash toggle-password" id="toggleResetNewPassword"></i>
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="confirm_password" id="resetConfirmPassword" placeholder="Confirm new password" required>
                <i class="fas fa-eye-slash toggle-password" id="toggleResetConfirmPassword"></i>
            </div>

            <button type="submit" class="btn-auth">Update Password</button>
        </form>

        <p class="auth-footer">Need a new code? <a href="forgot_password.php">Send another email</a></p>
    </div>
</main>

<script src="assets/js/auth.js"></script>

<?php include 'includes/footer.php'; ?>