<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$active_mode = $_SESSION['active_mode'] ?? 'user';
$author_type = '';
$author_id = 0;
$author_name = '';
$avatar_path = '';
$author_label = '';

$allowed_categories = ['general', 'academic', 'event', 'society', 'deadline', 'urgent'];
$allowed_priorities = ['low', 'normal', 'high', 'urgent'];

try {
    if ($active_mode === 'society' && !empty($_SESSION['active_society_id'])) {
        $society_id = (int) $_SESSION['active_society_id'];
        $stmt = $pdo->prepare("SELECT society_name, logo_path FROM societies WHERE id = ? LIMIT 1");
        $stmt->execute([$society_id]);
        $society = $stmt->fetch();

        if (!$society) {
            throw new RuntimeException('Selected society profile is unavailable.');
        }

        $author_type = 'society';
        $author_id = $society_id;
        $author_name = (string) $society['society_name'];
        $logo = trim((string) ($society['logo_path'] ?? ''));
        $avatar_path = $logo !== '' ? 'assets/images/uploads/' . $logo : '';
    } else {
        $user_id = (int) $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT fullname, category, avatar_url FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $user_category = strtolower(trim((string) ($user['category'] ?? '')));
        if (!$user || !in_array($user_category, ['admin', 'lecturer'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only admins, lecturers, or verified society profiles can post notices.']);
            exit;
        }

        $author_type = 'admin';
        $author_id = $user_id;
        $author_name = (string) $user['fullname'];
        $avatar_path = (string) ($user['avatar_url'] ?? '');
        $author_label = $user_category === 'lecturer' ? 'Lecturer' : 'Admin';
    }

    if ($author_type === 'society') {
        $author_label = 'Society';
    }

    $content = trim((string) ($_POST['content'] ?? ''));
    if ($content === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Notice content cannot be empty.']);
        exit;
    }

    if (mb_strlen($content) > 500) {
        $content = mb_substr($content, 0, 500);
    }

    $category = strtolower(trim((string) ($_POST['category'] ?? 'general')));
    if (!in_array($category, $allowed_categories, true)) {
        $category = 'general';
    }

    $priority = strtolower(trim((string) ($_POST['priority'] ?? 'normal')));
    if (!in_array($priority, $allowed_priorities, true)) {
        $priority = 'normal';
    }

    $expiry_date = null;
    $expiry_raw = trim((string) ($_POST['expiry_date'] ?? ''));
    if ($expiry_raw !== '') {
        $normalized = str_replace('T', ' ', $expiry_raw);
        $expiry_dt = DateTime::createFromFormat('Y-m-d H:i', $normalized);

        if ($expiry_dt === false) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid expiry date format.']);
            exit;
        }

        $expiry_date = $expiry_dt->format('Y-m-d H:i:s');
    }

    $insert = $pdo->prepare("INSERT INTO notices (author_type, author_id, content, category, priority, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$author_type, $author_id, $content, $category, $priority, $expiry_date]);

    echo json_encode([
        'success' => true,
        'notice' => [
            'author_type' => $author_type,
            'author_name' => $author_name,
            'author_label' => $author_label,
            'avatar_path' => $avatar_path,
            'content' => $content,
            'category' => $category,
            'priority' => $priority,
            'expiry_date' => $expiry_date,
        ],
    ]);
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? null) === 1054) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database needs notices v2 migration (category/priority/expiry).']);
        exit;
    }

    error_log('[post_notice.php] Failed to post notice: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to post notice right now.']);
} catch (Throwable $e) {
    error_log('[post_notice.php] Failed to post notice: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to post notice right now.']);
}
