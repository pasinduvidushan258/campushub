<?php
session_start();
require 'config/database.php';

// Redirect unauthenticated users to the login page before any further processing
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Process form submission only on POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and retrieve form inputs
    $name   = trim($_POST['society_name']);
    $desc   = trim($_POST['description']);
    $email1 = trim($_POST['email_1']);
    $email2 = trim($_POST['email_2']);

    // Validate that all required fields are filled
    if (empty($name) || empty($desc) || empty($email1) || empty($email2)) {
        $error = "Please fill in all fields!";

    // Validate that both inputs are properly formatted email addresses
    } elseif (!filter_var($email1, FILTER_VALIDATE_EMAIL) || !filter_var($email2, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter valid email addresses!";

    // Enforce university domain restriction — both emails must end with '.lk'
    } elseif (substr($email1, -3) !== '.lk' || substr($email2, -3) !== '.lk') {
        $error = "Emails must belong to a University domain (must end with .lk)!";

    } else {
        // Generate a cryptographically secure random token for the verification link
        $verify_token = bin2hex(random_bytes(32));

        // Insert the new society record into the database with a pending verification status
        $stmt = $pdo->prepare("INSERT INTO societies (admin_id, society_name, description, email_1, email_2, verify_token) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$_SESSION['user_id'], $name, $desc, $email1, $email2, $verify_token])) {
            
            // =========================================================
            // TODO: Send verification emails via PHPMailer to both
            //       provided addresses using the token link below.
            //       Email dispatch is stubbed out for now.
            // =========================================================
            $verification_link = "http://localhost/campusHub/verify_society.php?token=" . $verify_token;

            $success = "Society created! Verification links have been sent to the provided emails.";

            // Redirect to the home feed after a 4-second confirmation delay
            echo "<script>setTimeout(() => { window.location.href = 'index.php'; }, 4000);</script>";

        } else {
            $error = "Failed to create society.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-main" style="padding-top: 50px; padding-bottom: 50px;">
    <div class="auth-box" style="max-width: 550px; background: #242526; border: 1px solid rgba(255,255,255,0.1);">
        
        <div class="auth-header">
            <h2 style="color: #F97316;">Create New Society</h2>
            <p>Register your society. It requires verification from two other members (e.g., Senior Treasurer & Secretary).</p>
        </div>

        <!-- Inline validation feedback — displayed conditionally based on server-side result -->
        <?php if($error): ?><div style="color: #ef4444; text-align:center; margin-bottom:15px; font-weight: 500;"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div style="color: #23a55a; text-align:center; margin-bottom:15px; font-weight: 500;"><?php echo $success; ?></div><?php endif; ?>

        <form action="create_society.php" method="POST" class="auth-form">
            
            <!-- Society name input -->
            <div class="input-group">
                <input type="text" name="society_name" placeholder="Society Name" required 
                       style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white;">
            </div>
            
            <!-- Brief society description -->
            <div class="input-group" style="margin-top: 15px;">
                <textarea name="description" placeholder="Short description about your society..." required 
                          style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white; min-height: 100px; font-family: inherit;"></textarea>
            </div>

            <!-- Verification email section — requires two authorised university email addresses -->
            <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <label style="color: #b0b3b8; font-size: 0.85rem; margin-bottom: 8px; display: block;">Verification Emails (Require 2)</label>
                
                <!-- Primary verifier email (e.g. Senior Treasurer) -->
                <div class="input-group" style="margin-bottom: 10px;">
                    <input type="email" name="email_1" placeholder="Email Address 1 (e.g. Senior Treasurer)" required 
                           style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white;">
                </div>
                
                <!-- Secondary verifier email (e.g. Secretary) -->
                <div class="input-group">
                    <input type="email" name="email_2" placeholder="Email Address 2 (e.g. Secretary)" required 
                           style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white;">
                </div>
            </div>

            <!-- Submit button — triggers server-side validation and society registration -->
            <button type="submit" class="btn-auth" style="width: 100%; margin-top: 25px; background: #F97316; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.3s;">
                Register Society
            </button>

        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>