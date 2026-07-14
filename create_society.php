<?php
session_start();
require 'config/database.php';
require_once 'config/app.php';

// PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include required PHPMailer files without Composer
require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

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
            
            $verification_link = 'https://campushub.byethost11.com/verify_society.php?token=' . urlencode($verify_token);

            // =========================================================
            // send verification emails to both provided email addresses using PHPMailer
            // =========================================================
            
            // Read SMTP credentials from .env file
            $env = parse_ini_file(__DIR__ . '/.env');
            if (!$env || empty($env['SMTP_EMAIL']) || empty($env['SMTP_APP_PASSWORD'])) {
                $error = "Society created in database, but failed to send verification emails. SMTP credentials are not configured.";
            } else {

                // Create a new PHPMailer instance and configure SMTP settings
                $mail = new PHPMailer(true);

                try {
                    // Server settings (match the proven SMTP configuration used elsewhere in the project)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $env['SMTP_EMAIL'];
                    $mail->Password   = $env['SMTP_APP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    // Recipients
                    $mail->setFrom($env['SMTP_EMAIL'], 'CampusHub System');
                    $mail->addAddress($email1); // Senior Treasurer
                    $mail->addAddress($email2); // Secretary

                    // Email Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Action Required: Verify New Society - CampusHub';

                    // Display the verification link as a styled button in the email body for better user experience
                    $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                        <h2 style='color: #F97316; text-align: center;'>CampusHub Society Verification</h2>
                        <p>Hello,</p>
                        <p>A new society named <b>{$name}</b> has been registered on CampusHub and requires your verification.</p>
                        <p>As an executive member, please click the button below to verify and approve the creation of this society:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$verification_link}' style='background-color: #F97316; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify & Approve Society</a>
                        </div>
                        <p style='color: #666; font-size: 0.9em;'>If the button doesn't work, copy and paste this link into your browser:<br> <a href='{$verification_link}'>{$verification_link}</a></p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 0.8em; color: #999; text-align: center;'>If you did not request this, please ignore this email.</p>
                    </div>
                ";

                    $mail->send();

                    $success = "Society created! Verification links have been sent to the provided emails.";

                    // Redirect to the home feed after a 4-second confirmation delay
                    echo "<script>setTimeout(() => { window.location.href = 'index.php'; }, 4000);</script>";

                } catch (Exception $e) {
                    $error = "Society created in database, but failed to send verification emails. Mailer Error: {$mail->ErrorInfo}";
                }
            }

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

        <?php if($error): ?><div style="color: #ef4444; text-align:center; margin-bottom:15px; font-weight: 500;"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div style="color: #23a55a; text-align:center; margin-bottom:15px; font-weight: 500;"><?php echo $success; ?></div><?php endif; ?>

        <form action="create_society.php" method="POST" class="auth-form">
            
            <div class="input-group">
                <input type="text" name="society_name" placeholder="Society Name" required 
                       style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white;">
            </div>
            
            <div class="input-group" style="margin-top: 15px;">
                <textarea name="description" placeholder="Short description about your society..." required 
                          style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white; min-height: 100px; font-family: inherit;"></textarea>
            </div>

            <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <label style="color: #b0b3b8; font-size: 0.85rem; margin-bottom: 8px; display: block;">Verification Emails (Require 2)</label>
                
                <div class="input-group" style="margin-bottom: 10px;">
                    <input type="email" name="email_1" placeholder="Email Address 1 (e.g. Senior Treasurer)" required 
                           style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white;">
                </div>
                
                <div class="input-group">
                    <input type="email" name="email_2" placeholder="Email Address 2 (e.g. Secretary)" required 
                           style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #18191a; color: white;">
                </div>
            </div>

            <button type="submit" class="btn-auth" style="width: 100%; margin-top: 25px; background: #F97316; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.3s;">
                Register Society
            </button>

        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>