<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/notification_helpers.php';

$message = '';
$isSuccess = false;

$token = trim((string) ($_GET['token'] ?? ''));
$reason = trim((string) ($_GET['reason'] ?? 'Administrative review could not verify the submission.'));

if ($token === '') {
    $message = 'No verification token provided.';
} else {
    try {
        $stmt = $pdo->prepare("SELECT id, admin_id, society_name, status FROM societies WHERE verify_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $society = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$society) {
            $message = 'Invalid or expired verification link.';
        } elseif ((string) ($society['status'] ?? '') === 'verified') {
            $message = 'This society is already verified and cannot be rejected now.';
        } else {
            $upd = $pdo->prepare("UPDATE societies SET verify_token = NULL WHERE id = ?");
            $upd->execute([(int) $society['id']]);

            campushub_notify_user($pdo, [
                'recipient_user_id' => (int) $society['admin_id'],
                'actor_society_id' => (int) $society['id'],
                'type' => 'society_verification_rejected',
                'title' => 'Your society verification was rejected',
                'message' => (string) ($society['society_name'] ?? 'Your society') . ' verification was rejected. Reason: ' . $reason,
                'entity_type' => 'society',
                'entity_id' => (int) $society['id'],
                'link_url' => 'create_society.php',
                'dedupe_key' => 'society-verify-rejected:' . (int) $society['id'],
            ]);

            $isSuccess = true;
            $message = 'Society verification was rejected and the society admin has been notified.';
        }
    } catch (Throwable $e) {
        $message = 'Unable to process rejection right now.';
    }
}
?>
<?php include 'includes/header.php'; ?>
<main style="padding: 100px 20px; text-align: center; color: white; min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <div style="background: #242526; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 40px; max-width: 550px; width: 100%;">
        <?php if($isSuccess): ?>
            <div style="width: 80px; height: 80px; background: rgba(35, 165, 90, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px;">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #23a55a;"></i>
            </div>
            <h2 style="font-size: 1.6rem; margin-bottom: 15px; color: #E4E6EB;">Rejection Saved</h2>
        <?php else: ?>
            <div style="width: 80px; height: 80px; background: rgba(228, 30, 63, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px;">
                <i class="fas fa-times-circle" style="font-size: 3rem; color: #e41e3f;"></i>
            </div>
            <h2 style="font-size: 1.6rem; margin-bottom: 15px; color: #E4E6EB;">Rejection Failed</h2>
        <?php endif; ?>

        <p style="color: #b0b3b8; line-height: 1.6; margin-bottom: 25px; font-size: 1rem;"><?= htmlspecialchars($message) ?></p>

        <a href="index.php" class="btn-auth" style="display: inline-block; background: #F97316; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; transition: background 0.2s;">
            Go to Homepage
        </a>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
