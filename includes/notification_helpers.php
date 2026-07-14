<?php

if (!function_exists('timeAgo')) {
    function timeAgo($datetime): string {
        if (empty($datetime)) {
            return 'Just now';
        }

        try {
            $then = new DateTime((string) $datetime);
            $now = new DateTime();
        } catch (Exception $e) {
            return 'Just now';
        }

        $diff = $now->getTimestamp() - $then->getTimestamp();

        if ($diff < 60) {
            return 'Just now';
        }

        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);
            return $mins . ' min' . ($mins === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 2592000) {
            $weeks = (int) floor($diff / 604800);
            return $weeks . ' week' . ($weeks === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 31536000) {
            $months = (int) floor($diff / 2592000);
            return $months . ' month' . ($months === 1 ? '' : 's') . ' ago';
        }

        $years = (int) floor($diff / 31536000);
        return $years . ' year' . ($years === 1 ? '' : 's') . ' ago';
    }
}

if (!function_exists('campushub_notifications_table_ready')) {
    function campushub_notifications_table_ready(PDO $pdo): bool {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
            $checked = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $checked = false;
        }

        return $checked;
    }
}

if (!function_exists('campushub_notify_user')) {
    function campushub_notify_user(PDO $pdo, array $payload): bool {
        if (!campushub_notifications_table_ready($pdo)) {
            return false;
        }

        $recipientId = (int) ($payload['recipient_user_id'] ?? 0);
        if ($recipientId <= 0) {
            return false;
        }

        $type = trim((string) ($payload['type'] ?? 'general'));
        $title = trim((string) ($payload['title'] ?? 'CampusHub Update'));
        $message = trim((string) ($payload['message'] ?? 'You have a new notification.'));

        $actorUserId = isset($payload['actor_user_id']) ? (int) $payload['actor_user_id'] : null;
        $actorSocietyId = isset($payload['actor_society_id']) ? (int) $payload['actor_society_id'] : null;
        $entityType = trim((string) ($payload['entity_type'] ?? ''));
        $entityId = isset($payload['entity_id']) ? (int) $payload['entity_id'] : null;
        $linkUrl = trim((string) ($payload['link_url'] ?? ''));
        $dedupeKey = trim((string) ($payload['dedupe_key'] ?? ''));

        try {
            $sql = "INSERT INTO notifications (
                        recipient_user_id,
                        actor_user_id,
                        actor_society_id,
                        type,
                        title,
                        message,
                        entity_type,
                        entity_id,
                        link_url,
                        dedupe_key,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $recipientId,
                $actorUserId,
                $actorSocietyId,
                $type,
                $title,
                $message,
                $entityType !== '' ? $entityType : null,
                $entityId,
                $linkUrl !== '' ? $linkUrl : null,
                $dedupeKey !== '' ? $dedupeKey : null,
            ]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('campushub_notify_many')) {
    function campushub_notify_many(PDO $pdo, array $recipientIds, array $payload): int {
        $sent = 0;
        $uniqueIds = [];

        foreach ($recipientIds as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $uniqueIds[$intId] = true;
            }
        }

        foreach (array_keys($uniqueIds) as $recipientId) {
            $row = $payload;
            $row['recipient_user_id'] = $recipientId;

            if (campushub_notify_user($pdo, $row)) {
                $sent++;
            }
        }

        return $sent;
    }
}

if (!function_exists('campushub_get_unread_notification_count')) {
    function campushub_get_unread_notification_count(PDO $pdo, int $userId): int {
        if ($userId <= 0 || !campushub_notifications_table_ready($pdo)) {
            return 0;
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('campushub_seed_due_event_reminders')) {
    function campushub_seed_due_event_reminders(PDO $pdo, int $userId): void {
        if ($userId <= 0 || !campushub_notifications_table_ready($pdo)) {
            return;
        }

        try {
            $reminderSql = "
                SELECT e.id, e.title, e.event_date, e.start_time
                FROM saved_events se
                INNER JOIN events e ON e.id = se.event_id
                WHERE se.user_id = ?
                  AND e.status = 'upcoming'
                  AND TIMESTAMP(e.event_date, e.start_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
            ";
            $stmt = $pdo->prepare($reminderSql);
            $stmt->execute([$userId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($events as $event) {
                $eventId = (int) ($event['id'] ?? 0);
                if ($eventId <= 0) {
                    continue;
                }

                $eventTs = strtotime((string) ($event['event_date'] ?? '') . ' ' . (string) ($event['start_time'] ?? ''));
                if (!$eventTs) {
                    continue;
                }

                $secondsToGo = $eventTs - time();
                $window = ($secondsToGo <= 3600) ? 'hour' : 'day';

                campushub_notify_user($pdo, [
                    'recipient_user_id' => $userId,
                    'type' => $window === 'hour' ? 'event_starts_in_1_hour' : 'event_starts_tomorrow',
                    'title' => $window === 'hour' ? 'Event starts in 1 hour' : 'Event starts tomorrow',
                    'message' => ($event['title'] ?? 'An event') . ($window === 'hour' ? ' starts in about 1 hour.' : ' is scheduled within the next 24 hours.'),
                    'entity_type' => 'event',
                    'entity_id' => $eventId,
                    'link_url' => 'event_details.php?id=' . $eventId,
                    'dedupe_key' => $window . '-reminder:' . $userId . ':' . $eventId . ':' . date('Y-m-d', $eventTs),
                ]);
            }

            $followedSql = "
                SELECT e.id, e.title, e.event_date, e.start_time
                FROM society_followers sf
                INNER JOIN events e ON e.society_id = sf.society_id
                WHERE sf.user_id = ?
                  AND e.status = 'upcoming'
                  AND TIMESTAMP(e.event_date, e.start_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
            ";
            $fStmt = $pdo->prepare($followedSql);
            $fStmt->execute([$userId]);
            $followedEvents = $fStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($followedEvents as $event) {
                $eventId = (int) ($event['id'] ?? 0);
                if ($eventId <= 0) {
                    continue;
                }

                $eventTs = strtotime((string) ($event['event_date'] ?? '') . ' ' . (string) ($event['start_time'] ?? ''));
                if (!$eventTs) {
                    continue;
                }

                $secondsToGo = $eventTs - time();
                $window = ($secondsToGo <= 3600) ? 'hour' : 'day';

                campushub_notify_user($pdo, [
                    'recipient_user_id' => $userId,
                    'type' => $window === 'hour' ? 'followed_event_starts_in_1_hour' : 'followed_event_starts_tomorrow',
                    'title' => $window === 'hour' ? 'Followed event starts in 1 hour' : 'Followed event starts tomorrow',
                    'message' => ($event['title'] ?? 'An event') . ($window === 'hour' ? ' starts in about 1 hour.' : ' is scheduled within the next 24 hours.'),
                    'entity_type' => 'event',
                    'entity_id' => $eventId,
                    'link_url' => 'event_details.php?id=' . $eventId,
                    'dedupe_key' => 'followed-' . $window . '-reminder:' . $userId . ':' . $eventId . ':' . date('Y-m-d', $eventTs),
                ]);
            }
        } catch (Throwable $e) {
            // Do not break page/API flow if reminders fail.
        }
    }
}
