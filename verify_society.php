<?php
session_start();
require 'config/database.php';

$message = "";
$is_success = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // find the society with the provided token
    $stmt = $pdo->prepare("SELECT id, society_name, status FROM societies WHERE verify_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $society = $stmt->fetch();

    if ($society) {
        if ($society['status'] === 'verified') {
            // Already verified society - just show a message that it's already verified and cannot be verified again
            $message = "This society (<b>{$society['society_name']}</b>) has already been verified!";
            $is_success = true;
        } else {
            // Society found and not yet verified - proceed to verify it by updating its status and clearing the token
            $update_stmt = $pdo->prepare("UPDATE societies SET status = 'verified', verify_token = NULL WHERE id = ?");
            
            if ($update_stmt->execute([$society['id']])) {
                $message = "Success! The society <b>{$society['society_name']}</b> has been successfully verified and activated. The admin can now switch to this profile.";
                $is_success = true;
            } else {
                $message = "Something went wrong while verifying. Please try again or contact support.";
            }
        }
    } else {
        $message = "Invalid or expired verification link! This society may have already been verified.";
    }
} else {
    $message = "No verification token provided in the URL!";
}
?>

<?php include 'includes/header.php'; ?>

<main style="padding: 100px 20px; text-align: center; color: white; min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    
    <div style="background: #242526; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 40px; max-width: 550px; width: 100%;">
        
        <?php if($is_success): ?>
            <div style="width: 80px; height: 80px; background: rgba(35, 165, 90, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px;">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #23a55a;"></i>
            </div>
            <h2 style="font-size: 1.6rem; margin-bottom: 15px; color: #E4E6EB;">Verification Successful!</h2>
            <p style="color: #b0b3b8; line-height: 1.6; margin-bottom: 25px; font-size: 1rem;">
                <?php echo $message; ?>
            </p>
        <?php else: ?>
            <div style="width: 80px; height: 80px; background: rgba(228, 30, 63, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px;">
                <i class="fas fa-times-circle" style="font-size: 3rem; color: #e41e3f;"></i>
            </div>
            <h2 style="font-size: 1.6rem; margin-bottom: 15px; color: #E4E6EB;">Verification Failed</h2>
            <p style="color: #b0b3b8; line-height: 1.6; margin-bottom: 25px; font-size: 1rem;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <a href="index.php" class="btn-auth" style="display: inline-block; background: #F97316; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; transition: background 0.2s;">
            Go to Homepage
        </a>
    </div>

</main>

<?php include 'includes/footer.php'; ?>