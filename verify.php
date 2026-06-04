<?php
// Load the database connection. $pdo object is available after this.
require 'config/database.php';

// These two variables hold feedback messages shown to the user.
$error = "";
$success = "";

// If the registration page redirected here with ?email=..., grab that value.
// If nothing was passed, default to an empty string.
$email = isset($_GET['email']) ? $_GET['email'] : '';

// Only run the verification logic when the form is actually submitted (POST).
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the email that was stored in the hidden form field.
    $email_to_verify = $_POST['email'];
    
    // The OTP form has 6 separate <input> boxes, each submitting as otp[].
    // implode() joins them into one 6-character string, e.g. ["3","8","1","4","2","7"] → "381427"
    $otp_array = $_POST['otp'];
    $entered_otp = implode('', $otp_array);

    // Basic validation: email must not be empty, and OTP must be exactly 6 digits.
    if (empty($email_to_verify) || strlen($entered_otp) != 6) {
        $error = "Please enter the valid 6-digit OTP.";
    } else {
        // Look for a user row where BOTH the email AND the otp column match.
        // Using a prepared statement to prevent SQL injection.
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND otp = ?");
        $stmt->execute([$email_to_verify, $entered_otp]);

        if ($stmt->rowCount() > 0) {
            // A matching row was found, so the OTP is correct.
            // is_verified = 1  → marks the account as confirmed.
            // otp = NULL       → deletes the used OTP so it cannot be reused.
            $update_stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL WHERE email = ?");
            $update_stmt->execute([$email_to_verify]);

            // Verification is complete. Redirect to login page.
            // ?verified=true lets login.php show a "verified successfully" message.
            // exit() stops any further PHP execution after the redirect header.
            header("Location: login.php?verified=true");
            exit();
        } else {
            // No matching row — either wrong OTP or it has already been cleared.
            $error = "Invalid or expired OTP! Please try again.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-main">
    <div class="auth-box" style="max-width: 450px; text-align: center;">
        
        <div style="font-size: 3rem; color: #F97316; margin-bottom: 20px;">
            <i class="fas fa-envelope-open-text"></i>
        </div>

        <div class="auth-header">
            <h2>Verify Your Email</h2>
            <!-- $email comes from the GET parameter set by the registration page. -->
            <!-- htmlspecialchars() prevents XSS by escaping < > " & characters. -->
            <p>We've sent a 6-digit confirmation code to <br> <b style="color: #F8FAFC;"><?php echo htmlspecialchars($email); ?></b></p>
        </div>

        <!-- Only render the error box if $error is not empty. -->
        <?php if(!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="verify.php" method="POST" class="auth-form" id="otpForm">
            <!-- Hidden field carries the email to the POST handler without showing it on screen. -->
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

            <!-- Six individual single-character inputs that submit as the otp[] array. -->
            <div class="otp-container">
                <input type="text" name="otp[]" class="otp-input" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" class="otp-input" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" class="otp-input" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" class="otp-input" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" class="otp-input" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" class="otp-input" maxlength="1" required autocomplete="off">
            </div>

            <button type="submit" class="btn-auth" style="width: 100%;">Verify Account</button>
        </form>

        <p class="auth-footer" style="margin-top: 25px;">Didn't receive the code? <a href="#" style="cursor: pointer;">Resend OTP</a></p>
    </div>
</main>

<script src="assets/js/auth.js"></script>

<?php include 'includes/footer.php'; ?>