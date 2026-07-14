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

$eventId = (int) ($_POST['event_id'] ?? 0);
$parentCommentId = (int) ($_POST['parent_comment_id'] ?? 0);
$content = trim((string) ($_POST['content'] ?? ''));
$userId = (int) getUserId();

if ($eventId <= 0 || $content === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Event and comment content are required.']);
    exit;
}

if (mb_strlen($content) > 800) {
    $content = mb_substr($content, 0, 800);
}

if (!campushub_notifications_table_ready($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Notifications table is not ready.']);
    exit;
}

try {
    $eventStmt = $pdo->prepare("SELECT e.id, e.title, s.id AS society_id, s.admin_id FROM events e INNER JOIN societies s ON s.id = e.society_id WHERE e.id = ? LIMIT 1");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit;
    }

    $parentOwnerId = null;
    if ($parentCommentId > 0) {
        $pStmt = $pdo->prepare("SELECT user_id FROM event_comments WHERE id = ? AND event_id = ? LIMIT 1");
        $pStmt->execute([$parentCommentId, $eventId]);
        $parentOwnerId = $pStmt->fetchColumn();

        if (!$parentOwnerId) {
            echo json_encode(['success' => false, 'message' => 'Parent comment not found.']);
            exit;
        }
    }

    $insert = $pdo->prepare("INSERT INTO event_comments (event_id, user_id, parent_comment_id, content) VALUES (?, ?, ?, ?)");
    $insert->execute([$eventId, $userId, $parentCommentId > 0 ? $parentCommentId : null, $content]);
    $commentId = (int) $pdo->lastInsertId();

    $eventOwnerId = (int) ($event['admin_id'] ?? 0);
    if ($eventOwnerId > 0 && $eventOwnerId !== $userId) {
        campushub_notify_user($pdo, [
            'recipient_user_id' => $eventOwnerId,
            'actor_user_id' => $userId,
            'actor_society_id' => (int) ($event['society_id'] ?? 0),
            'type' => 'post_comment',
            'title' => 'Your post received a new comment',
            'message' => (string) ($event['title'] ?? 'An event') . ' received a new comment.',
            'entity_type' => 'event_comment',
            'entity_id' => $commentId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'event-comment:' . $commentId . ':' . $eventOwnerId,
        ]);
    }

    if ($parentOwnerId && (int) $parentOwnerId !== $userId) {
        campushub_notify_user($pdo, [
            'recipient_user_id' => (int) $parentOwnerId,
            'actor_user_id' => $userId,
            'actor_society_id' => (int) ($event['society_id'] ?? 0),
            'type' => 'comment_reply',
            'title' => 'Someone replied to your comment',
            'message' => 'You have a new reply on an event comment thread.',
            'entity_type' => 'event_comment',
            'entity_id' => $commentId,
            'link_url' => 'event_details.php?id=' . $eventId,
            'dedupe_key' => 'comment-reply:' . $commentId . ':' . (int) $parentOwnerId,
        ]);
    }

    preg_match_all('/@([A-Za-z0-9_\.]+)/', $content, $matches);
    $handles = array_values(array_unique($matches[1] ?? []));

    if (!empty($handles)) {
        $allUsers = $pdo->query("SELECT id, fullname FROM users")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($handles as $handle) {
            $normalizedHandle = strtolower(str_replace(' ', '', $handle));
            foreach ($allUsers as $u) {
                $candidate = strtolower(str_replace(' ', '', (string) ($u['fullname'] ?? '')));
                $mentionedId = (int) ($u['id'] ?? 0);
                if ($candidate === $normalizedHandle && $mentionedId > 0 && $mentionedId !== $userId) {
                    campushub_notify_user($pdo, [
                        'recipient_user_id' => $mentionedId,
                        'actor_user_id' => $userId,
                        'actor_society_id' => (int) ($event['society_id'] ?? 0),
                        'type' => 'mentioned_in_comment',
                        'title' => 'You were mentioned in a post/comment',
                        'message' => 'You were mentioned in an event discussion.',
                        'entity_type' => 'event_comment',
                        'entity_id' => $commentId,
                        'link_url' => 'event_details.php?id=' . $eventId,
                        'dedupe_key' => 'mention:' . $commentId . ':' . $mentionedId,
                    ]);
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Comment posted successfully.',
        'comment_id' => $commentId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to post comment right now.']);
}
