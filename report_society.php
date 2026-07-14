<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/function.php';
require_once 'includes/notification_helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$reporterId = (int) getUserId();
$societyId = (int) ($_POST['society_id'] ?? 0);
$reason = trim((string) ($_POST['reason'] ?? ''));
$details = trim((string) ($_POST['details'] ?? ''));

if ($societyId <= 0 || $reason === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Society and reason are required.']);
    exit;
}

if (mb_strlen($reason) > 255) {
    $reason = mb_substr($reason, 0, 255);
}
if (mb_strlen($details) > 1200) {
    $details = mb_substr($details, 0, 1200);
}

try {
    $socStmt = $pdo->prepare("SELECT id, society_name, admin_id FROM societies WHERE id = ? LIMIT 1");
    $socStmt->execute([$societyId]);
    $society = $socStmt->fetch(PDO::FETCH_ASSOC);

    if (!$society) {
        echo json_encode(['success' => false, 'message' => 'Society not found.']);
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO society_reports (society_id, reporter_user_id, reason, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$societyId, $reporterId, $reason, $details !== '' ? $details : null]);
    $reportId = (int) $pdo->lastInsertId();

    // Notify society admin.
    if ((int) $society['admin_id'] !== $reporterId) {
        campushub_notify_user($pdo, [
            'recipient_user_id' => (int) $society['admin_id'],
            'actor_user_id' => $reporterId,
            'actor_society_id' => $societyId,
            'type' => 'society_reported',
            'title' => 'Society profile reported/flagged',
            'message' => (string) ($society['society_name'] ?? 'Your society') . ' was reported. Reason: ' . $reason,
            'entity_type' => 'society_report',
            'entity_id' => $reportId,
            'link_url' => 'society_profile.php?id=' . $societyId,
            'dedupe_key' => 'soc-report:' . $reportId . ':admin:' . (int) $society['admin_id'],
        ]);
    }

    // Notify all admin users as moderation signal.
    $admins = $pdo->query("SELECT id FROM users WHERE LOWER(category) = 'admin'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admins as $admin) {
        $adminId = (int) ($admin['id'] ?? 0);
        if ($adminId <= 0 || $adminId === $reporterId) {
            continue;
        }

        campushub_notify_user($pdo, [
            'recipient_user_id' => $adminId,
            'actor_user_id' => $reporterId,
            'actor_society_id' => $societyId,
            'type' => 'society_reported',
            'title' => 'Society profile reported/flagged',
            'message' => (string) ($society['society_name'] ?? 'A society') . ' was reported. Reason: ' . $reason,
            'entity_type' => 'society_report',
            'entity_id' => $reportId,
            'link_url' => 'society_profile.php?id=' . $societyId,
            'dedupe_key' => 'soc-report:' . $reportId . ':mod:' . $adminId,
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Report submitted.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to submit report right now.']);
}
