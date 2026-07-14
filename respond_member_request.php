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
$requestId = (int) ($_POST['request_id'] ?? 0);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));

if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

try {
    $reqStmt = $pdo->prepare("SELECT r.id, r.society_id, r.user_id, r.status, s.admin_id, s.society_name FROM society_member_requests r INNER JOIN societies s ON s.id = r.society_id WHERE r.id = ? LIMIT 1");
    $reqStmt->execute([$requestId]);
    $request = $reqStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found.']);
        exit;
    }

    if ((int) $request['admin_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not allowed.']);
        exit;
    }

    if ((string) $request['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Request already handled.']);
        exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';

    $upd = $pdo->prepare("UPDATE society_member_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $upd->execute([$newStatus, $userId, $requestId]);

    if ($newStatus === 'approved') {
        $memberInsert = $pdo->prepare("INSERT IGNORE INTO society_members (society_id, user_id, joined_at) VALUES (?, ?, NOW())");
        $memberInsert->execute([(int) $request['society_id'], (int) $request['user_id']]);
    }

    campushub_notify_user($pdo, [
        'recipient_user_id' => (int) $request['user_id'],
        'actor_user_id' => $userId,
        'actor_society_id' => (int) $request['society_id'],
        'type' => $newStatus === 'approved' ? 'member_request_approved' : 'member_request_rejected',
        'title' => $newStatus === 'approved' ? 'Member request approved' : 'Member request rejected',
        'message' => 'Your membership request for ' . (string) ($request['society_name'] ?? 'the society') . ' was ' . $newStatus . '.',
        'entity_type' => 'society_member_request',
        'entity_id' => $requestId,
        'link_url' => 'society_profile.php?id=' . (int) $request['society_id'],
        'dedupe_key' => 'member-request-' . $newStatus . ':' . $requestId,
    ]);

    echo json_encode(['success' => true, 'status' => $newStatus]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process request right now.']);
}
