<?php
session_start();
// If the user is not logged in, redirect to login page.
// This prevents unauthenticated users from accessing this page directly.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<main style="padding: 100px 20px; text-align: center; color: white; min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    
    <!-- 
        Pending verification notice card.
        Shown to a society admin whose society has not yet been verified by
        the Senior Treasurer and Secretary via their university email links.
    -->
    <div style="background: #242526; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 40px; max-width: 500px; width: 100%;">
        
        <!-- Hourglass icon visually communicates a "waiting" state to the user. -->
        <i class="fas fa-hourglass-half" style="font-size: 3.5rem; color: #F97316; margin-bottom: 20px;"></i>
        
        <h2 style="font-size: 1.5rem; margin-bottom: 15px;">Account Pending Verification</h2>
        
        <!-- Explains what action is still required and who needs to take it. -->
        <p style="color: #b0b3b8; line-height: 1.6; margin-bottom: 25px; font-size: 0.95rem;">
            Your society is currently waiting for verification. Please make sure that the <strong>Senior Treasurer</strong> and <strong>Secretary</strong> click the verification links we sent to their university (.lk) email addresses.
        </p>
        
        <!-- Highlighted info box — tells the user what happens once verification is complete. -->
        <div style="background: rgba(249, 115, 22, 0.1); border-left: 4px solid #F97316; padding: 12px 15px; border-radius: 4px; text-align: left; margin-bottom: 25px;">
            <i class="fas fa-info-circle" style="color: #F97316; margin-right: 8px;"></i>
            <span style="color: #E4E6EB; font-size: 0.85rem;">Once both members verify, you will be able to switch to this society account and manage events.</span>
        </div>

        <!-- Fallback navigation button — lets the user return to the homepage while they wait. -->
        <a href="index.php" class="btn-auth" style="display: inline-block; background: #3A3B3C; color: #E4E6EB; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-weight: 500; transition: background 0.2s;">
            Go Back Home
        </a>
    </div>

</main>

<?php include 'includes/footer.php'; ?>