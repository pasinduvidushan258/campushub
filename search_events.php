<?php

require_once 'config/database.php';

$q = $_GET['q'] ?? '';

if (empty($q)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.id,
           e.title,
           s.society_name
    FROM events e
    JOIN societies s
        ON e.society_id = s.id
    WHERE e.title LIKE ?
    ORDER BY e.title ASC
    LIMIT 5
");

$stmt->execute([$q . '%']);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);