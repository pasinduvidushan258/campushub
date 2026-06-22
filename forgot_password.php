<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/security_email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $pdo->prepare('SELECT id, fullname, email, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = 'We could not find an account with that email address.';
        } elseif ((int) $user['is_verified'] !== 1) {
            $error = 'Please verify your account email before resetting your password.';
        } else {
            $code = (string) random_int(100000, 999999);

            $_SESSION['password_reset'] = [
                'user_id' => (int) $user['id'],
                'email' => $user['email'],
                'code' => $code,
                'expires_at' => time() + 900,
            ];

            $mail_result = campushub_send_security_code(
                $user['email'],
                $user['fullname'],
                'CampusHub Password Reset Code',
                'Password reset verification',
                'Use the code below to continue resetting your CampusHub password:',
                $code,
                'This code will expire in 15 minutes.'
            );

            if ($mail_result['success']) {
                header('Location: reset_password.php?sent=true');
                exit();
            }

            $error = 'We could not send the reset code. ' . $mail_result['error'];
        }
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-main">
    <div class="auth-box">
        <div class="auth-header">
            <h2>Forgot Password</h2>
            <p>We will email you a verification code to reset your password.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" class="auth-form">
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="Enter your account email" required>
            </div>

            <button type="submit" class="btn-auth">Send Reset Code</button>
        </form>

        <p class="auth-footer">Remember your password? <a href="login.php">Back to login</a></p>
    </div>
</main>

<?php include 'includes/footer.php'; ?>