<?php
// session_start() must be called before reading or writing any $_SESSION data.
session_start();

// Load the database connection. $pdo object becomes available after this.
require 'config/database.php';
require_once 'includes/notification_helpers.php';

$error = "";
$success = "";

// If the user was redirected here after a successful OTP verification,
// show a confirmation message to let them know they can now log in.
if (isset($_GET['verified']) && $_GET['verified'] == 'true') {
    $success = "Email verified successfully! You can now login.";
}

// Only run the login logic when the form is submitted via POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation — both fields must be filled before hitting the database.
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Fetch the user record matching the submitted email address.
        // We retrieve the hashed password and is_verified flag for the checks below.
        $stmt = $pdo->prepare("SELECT id, fullname, password, category, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Block login if the user has not yet confirmed their email via OTP.
            // is_verified is set to 1 only after the OTP step in verify.php.
            if ($user['is_verified'] == 0) {
                $error = "Please verify your email address before logging in. Check your inbox for the OTP.";
            } else {
                // password_verify() compares the plain-text input against the bcrypt hash stored in the database.
                // This is the secure way to check passwords — never compare plain text directly.
                if (password_verify($password, $user['password'])) {

                    // Credentials are valid. Store the essential user details in the session
                    // so other pages can identify who is logged in without re-querying the database.
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['category'] = $user['category'];
                    $_SESSION['email']    = $email;

                    try {
                        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
                        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 300);
                        $fingerprint = hash('sha256', strtolower($ip . '|' . $ua));

                        $fpStmt = $pdo->prepare("SELECT id FROM user_login_fingerprints WHERE user_id = ? AND fingerprint = ? LIMIT 1");
                        $fpStmt->execute([(int) $user['id'], $fingerprint]);
                        $knownDevice = (bool) $fpStmt->fetchColumn();

                        if ($knownDevice) {
                            $touchStmt = $pdo->prepare("UPDATE user_login_fingerprints SET last_ip = ?, last_seen_at = NOW() WHERE user_id = ? AND fingerprint = ?");
                            $touchStmt->execute([$ip, (int) $user['id'], $fingerprint]);
                        } else {
                            $insertFp = $pdo->prepare("INSERT INTO user_login_fingerprints (user_id, fingerprint, last_ip, created_at, last_seen_at) VALUES (?, ?, ?, NOW(), NOW())");
                            $insertFp->execute([(int) $user['id'], $fingerprint, $ip]);

                            campushub_notify_user($pdo, [
                                'recipient_user_id' => (int) $user['id'],
                                'actor_user_id' => (int) $user['id'],
                                'type' => 'unknown_login_device',
                                'title' => 'New login from unknown device/location',
                                'message' => 'A new device/location login was detected for your account (IP: ' . $ip . ').',
                                'entity_type' => 'account',
                                'entity_id' => (int) $user['id'],
                                'link_url' => 'settings.php',
                                'dedupe_key' => 'unknown-login:' . (int) $user['id'] . ':' . $fingerprint,
                            ]);
                        }
                    } catch (Throwable $e) {
                        // Login should not fail if notification/device tracking fails.
                    }

                    // Login successful — send the user to the home page.
                    header("Location: index.php");
                    exit(); // Stop further PHP execution after the redirect header is sent.
                } else {
                    // Password did not match. Use a generic message to avoid revealing
                    // whether the email exists in the database (security best practice).
                    $error = "Invalid email or password.";
                }
            }
        } else {
            // No user found with this email. Same generic message as above for the same reason.
            $error = "Invalid email or password.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-main">
    <div class="auth-box">
        <div class="auth-header">
            <h2>Welcome Back!</h2>
            <p>Login to your CampusHub account</p>
        </div>

        <!-- Display server-side error messages returned from the PHP login block above. -->
        <?php if(!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Display success messages, e.g. the post-verification confirmation set above. -->
        <?php if(!empty($success)): ?>
            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <!-- Eye icon toggles password visibility — wired up by the JS block below. -->
                <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
            </div>

            <div class="auth-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-auth">Login to CampusHub</button>
        </form>

        <p class="auth-footer">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</main>

<script src="assets/js/auth.js"></script>

<?php include 'includes/footer.php'; ?>