<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';

$query = trim((string) ($_GET['q'] ?? ''));

$payload = [
    'query' => $query,
    'events' => [],
    'societies' => [],
    'notices' => [],
    'users' => []
];

if ($query === '') {
    echo json_encode($payload);
    exit;
}

$startsWith = $query . '%';
$contains = '%' . $query . '%';

$runSafe = static function (callable $runner): array {
    try {
        return $runner();
    } catch (Throwable $e) {
        error_log('[search_global.php] ' . $e->getMessage());
        return [];
    }
};

$payload['events'] = $runSafe(static function () use ($pdo, $startsWith, $contains): array {
    $stmt = $pdo->prepare(
        "SELECT e.id, e.title, COALESCE(s.society_name, 'Unknown society') AS society_name
         FROM events e
         LEFT JOIN societies s ON s.id = e.society_id
         WHERE e.title LIKE :startsWith
            OR e.title LIKE :contains
            OR s.society_name LIKE :contains
         ORDER BY e.created_at DESC
         LIMIT 4"
    );
    $stmt->execute([
        ':startsWith' => $startsWith,
        ':contains' => $contains
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'subtitle' => (string) ($row['society_name'] ?? ''),
            'url' => 'event_details.php?id=' . (int) ($row['id'] ?? 0),
            'type' => 'event'
        ];
    }, $rows);
});

$payload['societies'] = $runSafe(static function () use ($pdo, $startsWith, $contains): array {
    $stmt = $pdo->prepare(
        "SELECT id, society_name, faculty
         FROM societies
         WHERE status = 'verified'
           AND (
               society_name LIKE :startsWith
               OR society_name LIKE :contains
               OR faculty LIKE :contains
               OR description LIKE :contains
           )
         ORDER BY society_name ASC
         LIMIT 4"
    );
    $stmt->execute([
        ':startsWith' => $startsWith,
        ':contains' => $contains
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        $faculty = trim((string) ($row['faculty'] ?? ''));
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['society_name'] ?? ''),
            'subtitle' => $faculty !== '' ? $faculty : 'Campus society',
            'url' => 'society_profile.php?id=' . (int) ($row['id'] ?? 0),
            'type' => 'society'
        ];
    }, $rows);
});

$payload['notices'] = $runSafe(static function () use ($pdo, $startsWith, $contains): array {
    $stmt = $pdo->prepare(
        "SELECT n.id,
                LEFT(TRIM(n.content), 90) AS excerpt,
                n.author_type,
                n.author_id,
                u.fullname AS admin_name,
                s.society_name
         FROM notices n
         LEFT JOIN users u ON n.author_type = 'admin' AND n.author_id = u.id
         LEFT JOIN societies s ON n.author_type = 'society' AND n.author_id = s.id
         WHERE n.content LIKE :startsWith OR n.content LIKE :contains
         ORDER BY n.created_at DESC
         LIMIT 3"
    );
    $stmt->execute([
        ':startsWith' => $startsWith,
        ':contains' => $contains
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        $isSociety = (($row['author_type'] ?? '') === 'society');
        $author = $isSociety ? ($row['society_name'] ?? 'Society notice') : ($row['admin_name'] ?? 'Campus notice');
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['excerpt'] ?? ''),
            'subtitle' => (string) $author,
            'url' => 'notices.php',
            'type' => 'notice'
        ];
    }, $rows);
});

$payload['users'] = $runSafe(static function () use ($pdo, $startsWith, $contains): array {
    $stmt = $pdo->prepare(
        "SELECT id, fullname, category
         FROM users
         WHERE fullname LIKE :startsWith
            OR fullname LIKE :contains
         ORDER BY fullname ASC
         LIMIT 4"
    );
    $stmt->execute([
        ':startsWith' => $startsWith,
        ':contains' => $contains
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        $role = trim((string) ($row['category'] ?? 'student'));
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['fullname'] ?? ''),
            'subtitle' => ucfirst($role),
            'url' => 'user_profile.php?id=' . (int) ($row['id'] ?? 0),
            'type' => 'user'
        ];
    }, $rows);
});

echo json_encode($payload);
