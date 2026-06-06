<?php
// කිසිම එරර් මැසේජ් එකක් ඇවිත් පණිවිඩය කැඩෙන එක නවත්තන්න ob_start දානවා
ob_start();
session_start();
require 'config/database.php';

ob_clean(); // අනවශ්‍ය හිස්තැන් මකා දැමීම
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// POST ක්‍රමයට දත්ත ලබා ගැනීම
$type = $_POST['type'] ?? 'user';
$id   = $_POST['id']   ?? 0;

if ($type === 'society') {
    $stmt = $pdo->prepare("SELECT id, society_name FROM societies WHERE id = ? AND admin_id = ? AND status = 'verified'");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $society = $stmt->fetch();

    if ($society) {
        $_SESSION['active_mode']         = 'society';
        $_SESSION['active_society_id']   = $society['id'];
        $_SESSION['active_society_name'] = $society['society_name'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid society']);
        exit();
    }
} else {
    $_SESSION['active_mode'] = 'user';
    unset($_SESSION['active_society_id']);
    unset($_SESSION['active_society_name']);
}

// හැමවෙලේම අනිවාර්යයෙන්ම Home Page එකට යවනවා
$home_page_url = '/campushub/index.php'; 

echo json_encode(['success' => true, 'redirect_url' => $home_page_url]);
exit();
?>