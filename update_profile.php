<?php
session_start();
require_once 'config/database.php';

// logged in user not found, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$photo_type = $_POST['photo_type'] ?? ''; // identify if it's 'avatar' or 'cover'

// If a photo was uploaded without errors...
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    
    // Directory where photos will be saved
    $upload_dir = 'assets/images/uploads/';
    
    // Create the directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate a unique filename for the photo (to avoid conflicts with other users' photos)
    $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $file_name = time() . '_' . $photo_type . '_' . $user_id . '.' . $file_extension;
    $target_path = $upload_dir . $file_name;

    // Move the uploaded photo to the target directory
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
        
        if (isset($pdo)) {
            // Update the database depending on whether it's a cover or avatar photo
            if ($photo_type === 'cover') {
                $stmt = $pdo->prepare("UPDATE users SET cover_url = ? WHERE id = ?");
                $stmt->execute([$target_path, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                $stmt->execute([$target_path, $user_id]);
                
                // If it's an avatar update, also update the session variable so the new avatar shows up immediately in the header without needing to log out and back in.
                $_SESSION['avatar_url'] = $target_path;
            }
        }
    }
}

// After everything is done, redirect back to the profile page with a success message
header("Location: my_profile.php?success=photo_updated");
exit();
?>