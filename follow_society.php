<?php
// follow_society.php
session_start();
require_once 'config/database.php';
require_once 'includes/notification_helpers.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to follow societies.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$society_id = (int) ($_POST['society_id'] ?? 0);

if (!$society_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid society.']);
    exit();
}

// Block following only the society the user is CURRENTLY acting as (active
// society via switch_role.php) — not every society they happen to administer.
$activeMode      = $_SESSION['active_mode'] ?? 'user';
$activeSocietyId = (int) ($_SESSION['active_society_id'] ?? 0);
if ($activeMode === 'society' && $activeSocietyId === $society_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot follow your own society.']);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM society_followers WHERE user_id = ? AND society_id = ?");
$stmt->execute([$user_id, $society_id]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("DELETE FROM society_followers WHERE id = ?");
    $stmt->execute([$existing['id']]);
    $following = false;
} else {
    $stmt = $pdo->prepare("INSERT INTO society_followers (society_id, user_id) VALUES (?, ?)");
    $stmt->execute([$society_id, $user_id]);
    $following = true;

    try {
        $societyStmt = $pdo->prepare("SELECT admin_id, society_name FROM societies WHERE id = ? LIMIT 1");
        $societyStmt->execute([$society_id]);
        $societyData = $societyStmt->fetch(PDO::FETCH_ASSOC);

        if ($societyData && (int) $societyData['admin_id'] !== $user_id) {
            campushub_notify_user($pdo, [
                'recipient_user_id' => (int) $societyData['admin_id'],
                'actor_user_id' => $user_id,
                'actor_society_id' => $society_id,
                'type' => 'society_followed',
                'title' => 'Someone followed your society',
                'message' => 'A user started following ' . (string) ($societyData['society_name'] ?? 'your society') . '.',
                'entity_type' => 'society',
                'entity_id' => $society_id,
                'link_url' => 'society_profile.php?id=' . $society_id,
                'dedupe_key' => 'soc-follow:' . $society_id . ':' . $user_id,
            ]);
        }
    } catch (Throwable $e) {
        // Keep follow endpoint resilient.
    }
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM society_followers WHERE society_id = ?");
$countStmt->execute([$society_id]);
$followers = (int) $countStmt->fetchColumn();

if ($following) {
    $milestones = [10, 25, 50, 100, 250, 500, 1000];
    if (in_array($followers, $milestones, true)) {
        try {
            $societyMetaStmt = $pdo->prepare("SELECT admin_id, society_name FROM societies WHERE id = ? LIMIT 1");
            $societyMetaStmt->execute([$society_id]);
            $societyMeta = $societyMetaStmt->fetch(PDO::FETCH_ASSOC);

            if ($societyMeta && (int) $societyMeta['admin_id'] > 0) {
                campushub_notify_user($pdo, [
                    'recipient_user_id' => (int) $societyMeta['admin_id'],
                    'actor_society_id' => $society_id,
                    'type' => 'society_follower_milestone',
                    'title' => 'Society reached follower milestone',
                    'message' => (string) ($societyMeta['society_name'] ?? 'Your society') . ' reached ' . $followers . ' followers.',
                    'entity_type' => 'society',
                    'entity_id' => $society_id,
                    'link_url' => 'society_profile.php?id=' . $society_id,
                    'dedupe_key' => 'society-milestone:' . $society_id . ':' . $followers,
                ]);
            }
        } catch (Throwable $e) {
            // Keep follow endpoint resilient even if milestone notify fails.
        }
    }
}

echo json_encode(['success' => true, 'following' => $following, 'followers' => $followers]);
exit();
?>