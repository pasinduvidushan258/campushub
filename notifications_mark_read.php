<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/notification_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

if (!campushub_notifications_table_ready($pdo)) {
    echo json_encode(['success' => true, 'unread_count' => 0]);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$notificationId = isset($body['notification_id']) ? (int) $body['notification_id'] : 0;
$markAll = !empty($body['mark_all']);

try {
    if ($markAll) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
    } elseif ($notificationId > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_user_id = ?");
        $stmt->execute([$notificationId, $userId]);
    }

    echo json_encode([
        'success' => true,
        'unread_count' => campushub_get_unread_notification_count($pdo, $userId),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update notifications.']);
}
