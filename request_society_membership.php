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

$userId = (int) getUserId();
$societyId = (int) ($_POST['society_id'] ?? 0);

if ($societyId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid society id.']);
    exit;
}

try {
    $socStmt = $pdo->prepare("SELECT id, society_name, admin_id, status FROM societies WHERE id = ? LIMIT 1");
    $socStmt->execute([$societyId]);
    $society = $socStmt->fetch(PDO::FETCH_ASSOC);

    if (!$society || (string) ($society['status'] ?? '') !== 'verified') {
        echo json_encode(['success' => false, 'message' => 'Society is not available.']);
        exit;
    }

    if ((int) $society['admin_id'] === $userId) {
        echo json_encode(['success' => false, 'message' => 'You already manage this society.']);
        exit;
    }

    $memberStmt = $pdo->prepare("SELECT id FROM society_members WHERE society_id = ? AND user_id = ? LIMIT 1");
    $memberStmt->execute([$societyId, $userId]);
    if ($memberStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'You are already a member.']);
        exit;
    }

    $pendingStmt = $pdo->prepare("SELECT id FROM society_member_requests WHERE society_id = ? AND user_id = ? AND status = 'pending' LIMIT 1");
    $pendingStmt->execute([$societyId, $userId]);
    if ($pendingStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'A membership request is already pending.']);
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO society_member_requests (society_id, user_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $insert->execute([$societyId, $userId]);
    $requestId = (int) $pdo->lastInsertId();

    campushub_notify_user($pdo, [
        'recipient_user_id' => (int) $society['admin_id'],
        'actor_user_id' => $userId,
        'actor_society_id' => $societyId,
        'type' => 'new_member_request',
        'title' => 'New member request to join society',
        'message' => 'A new user requested to join ' . (string) ($society['society_name'] ?? 'your society') . '.',
        'entity_type' => 'society_member_request',
        'entity_id' => $requestId,
        'link_url' => 'society_dashboard.php',
        'dedupe_key' => 'member-request:' . $requestId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Membership request sent.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to send request right now.']);
}
