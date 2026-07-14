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

$userId = (int) $_SESSION['user_id'];

campushub_seed_due_event_reminders($pdo, $userId);

if (!campushub_notifications_table_ready($pdo)) {
    echo json_encode([
        'success' => true,
        'unread_count' => 0,
        'notifications' => [],
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, type, title, message, link_url, is_read, created_at
                           FROM notifications
                           WHERE recipient_user_id = ?
                           ORDER BY is_read ASC, created_at DESC
                           LIMIT 50");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (int) $row['id'],
            'type' => (string) ($row['type'] ?? 'general'),
            'title' => (string) ($row['title'] ?? 'CampusHub Update'),
            'message' => (string) ($row['message'] ?? ''),
            'link_url' => (string) ($row['link_url'] ?? ''),
            'is_read' => (int) ($row['is_read'] ?? 0) === 1,
            'time_ago' => timeAgo($row['created_at'] ?? null),
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => campushub_get_unread_notification_count($pdo, $userId),
        'notifications' => $items,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load notifications.']);
}
