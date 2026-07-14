<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/security_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is in society mode
if (!isset($_SESSION['active_mode']) || $_SESSION['active_mode'] !== 'society') {
    header("Location: my_profile.php");
    exit();
}

$extra_stylesheets = ['assets/css/manage-account.css'];
$page_title = 'Manage Account - CampusHub';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get society information
$society_id = $_SESSION['active_society_id'] ?? null;
$society_name = $_SESSION['active_society_name'] ?? '';

if ($society_id) {
    $stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ? AND admin_id = ?");
    $stmt->execute([$society_id, $user_id]);
    $society = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$society) {
        header("Location: my_profile.php");
        exit();
    }
}

// Create society_managers table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS society_managers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            society_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            role ENUM('owner', 'manager') DEFAULT 'manager',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (society_id) REFERENCES societies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_society_email (society_id, email)
        )
    ");
} catch (PDOException $e) {
    // Table might already exist or there might be a foreign key issue
    // We'll continue anyway
}

// Handle form submission - Change Society Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $error = 'Current password is required.';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = 'New password and confirmation are required.';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!password_verify($current_password, $society['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        try {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $update_stmt = $pdo->prepare("UPDATE societies SET password = ? WHERE id = ?");
            if ($update_stmt->execute([$hashed_password, $society_id])) {
                $success = 'Society password changed successfully!';
                // Refresh society data
                $stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ? AND admin_id = ?");
                $stmt->execute([$society_id, $user_id]);
                $society = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle form submission - Change Society Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_email') {
    $current_password = $_POST['current_password'] ?? '';
    $new_email = trim($_POST['new_email'] ?? '');
    $confirm_email = trim($_POST['confirm_email'] ?? '');

    // Validation
    if (empty($current_password)) {
        $error = 'Current password is required.';
    } elseif (empty($new_email) || empty($confirm_email)) {
        $error = 'New email and confirmation are required.';
    } elseif ($new_email !== $confirm_email) {
        $error = 'Emails do not match.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($new_email === $society['email']) {
        $error = 'New email must be different from current email.';
    } elseif (!password_verify($current_password, $society['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        try {
            // Check if email already exists in database
            $check_stmt = $pdo->prepare("SELECT id FROM societies WHERE email = ? AND id != ?");
            $check_stmt->execute([$new_email, $society_id]);
            
            if ($check_stmt->fetch()) {
                $error = 'This email is already registered. Please use a different email.';
            } else {
                // Update email in database
                $update_stmt = $pdo->prepare("UPDATE societies SET email = ? WHERE id = ?");
                if ($update_stmt->execute([$new_email, $society_id])) {
                    $success = 'Society email updated successfully!';
                    // Refresh society data
                    $stmt = $pdo->prepare("SELECT * FROM societies WHERE id = ? AND admin_id = ?");
                    $stmt->execute([$society_id, $user_id]);
                    $society = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Failed to update email. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle form submission - Add Manager
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manager') {
    $manager_email = trim($_POST['manager_email'] ?? '');
    
    if (empty($manager_email)) {
        $error = 'Please enter an email address.';
    } elseif (!filter_var($manager_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email is already a manager
            $check_stmt = $pdo->prepare("SELECT id FROM society_managers WHERE society_id = ? AND email = ?");
            $check_stmt->execute([$society_id, $manager_email]);
            
            if ($check_stmt->fetch()) {
                $error = 'This email is already a manager for this society.';
            } else {
                // Add manager to database
                $insert_stmt = $pdo->prepare("INSERT INTO society_managers (society_id, email, role) VALUES (?, ?, 'manager')");
                if ($insert_stmt->execute([$society_id, $manager_email])) {
                    $success = 'Manager added successfully! They can now manage this society account.';
                } else {
                    $error = 'Failed to add manager. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle remove manager
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_manager') {
    $manager_id = intval($_POST['manager_id'] ?? 0);
    
    if ($manager_id > 0) {
        try {
            // Don't allow removing the primary owner
            $check_stmt = $pdo->prepare("SELECT role FROM society_managers WHERE id = ? AND society_id = ?");
            $check_stmt->execute([$manager_id, $society_id]);
            $manager = $check_stmt->fetch();
            
            if ($manager && $manager['role'] === 'owner') {
                $error = 'Cannot remove the primary owner.';
            } else {
                $delete_stmt = $pdo->prepare("DELETE FROM society_managers WHERE id = ? AND society_id = ?");
                if ($delete_stmt->execute([$manager_id, $society_id])) {
                    $success = 'Manager removed successfully.';
                } else {
                    $error = 'Failed to remove manager. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all managers for this society
$managers = [];
try {
    $stmt = $pdo->prepare("SELECT id, email, role, created_at FROM society_managers WHERE society_id = ? ORDER BY role = 'owner' DESC, created_at ASC");
    $stmt->execute([$society_id]);
    $managers = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet, continue with empty managers array
    $managers = [];
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $page_title; ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap');

        :root {
            --bg-main: #070d1a;
            --panel: rgba(14, 24, 46, 0.88);
            --panel-border: rgba(148, 163, 184, 0.17);
            --field-bg: rgba(8, 15, 30, 0.78);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #f97316;
            --accent-2: #fb923c;
            --success: #4ade80;
            --danger: #f87171;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Sora', 'Segoe UI', sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(circle at 14% 18%, rgba(249, 115, 22, 0.14), transparent 38%),
                radial-gradient(circle at 88% 8%, rgba(56, 189, 248, 0.12), transparent 30%),
                linear-gradient(180deg, #050913 0%, #070d1a 45%, #060a12 100%);
            position: relative;
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.24;
            z-index: 0;
            pointer-events: none;
        }

        body::before {
            top: 140px;
            left: -110px;
            background: #f97316;
        }

        body::after {
            right: -130px;
            bottom: -40px;
            background: #0ea5e9;
        }

        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.2rem 1.2rem 3rem;
        }

        .container {
            width: 100%;
            max-width: 860px;
        }

        .profile-header,
        .settings-card {
            background: linear-gradient(160deg, rgba(19, 33, 61, 0.92), rgba(11, 21, 40, 0.9));
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            box-shadow:
                0 24px 56px rgba(2, 6, 23, 0.66),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
        }

        .profile-header {
            padding: 1.35rem 1.6rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .profile-header .avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(140deg, var(--accent), var(--accent-2));
            color: #111827;
            font-size: 1.4rem;
            box-shadow: 0 12px 22px rgba(249, 115, 22, 0.28);
        }

        .profile-header .info h3 {
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 700;
        }

        .profile-header .info p {
            margin-top: 0.35rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .account-email-line i {
            margin-right: 0.4rem;
            color: var(--accent);
        }

        .badge {
            border-radius: 999px;
            padding: 0.2rem 0.68rem;
            font-size: 0.7rem;
            font-weight: 700;
            background: rgba(249, 115, 22, 0.16);
            color: #fdba74;
            border: 1px solid rgba(249, 115, 22, 0.3);
        }

        .settings-card {
            padding: 1.85rem 1.7rem 1.9rem;
            position: relative;
            overflow: hidden;
        }

        .settings-card::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -90px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle at center, rgba(249, 115, 22, 0.22), transparent 68%);
            pointer-events: none;
        }

        .settings-card h2 {
            position: relative;
            z-index: 1;
            font-size: 1.6rem;
            margin-bottom: 0.24rem;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            color: #fff;
            font-weight: 700;
        }

        .settings-card h2 i {
            color: var(--accent);
        }

        .settings-card .sub {
            position: relative;
            z-index: 1;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1.35rem;
            padding-left: 2rem;
        }

        .manage-btn-wrapper {
            margin: 0.9rem 0 0.7rem;
        }

        .btn-manage {
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 0.82rem 1.4rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.26);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .btn-manage:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(249, 115, 22, 0.33);
        }

        .manage-panel {
            display: none;
            margin-top: 1.5rem;
            padding-top: 1.3rem;
            border-top: 1px solid rgba(148, 163, 184, 0.24);
            animation: fadeIn 0.24s ease;
        }

        .manage-panel.open {
            display: block;
        }

        .manage-panel h3 {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
        }

        .manage-panel h3 i {
            color: var(--accent);
        }

        .manage-panel .desc {
            margin: 0.35rem 0 1rem;
            font-size: 0.89rem;
            color: var(--text-muted);
            line-height: 1.55;
        }

        .desc.compact {
            margin-bottom: 1rem;
            padding-left: 0;
        }

        .highlight-accent {
            color: var(--accent);
        }

        .settings-alert {
            margin-bottom: 1rem;
            border-radius: 12px;
            padding: 0.74rem 0.9rem;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .settings-alert.success {
            background: rgba(34, 197, 94, 0.12);
            color: var(--success);
            border: 1px solid rgba(74, 222, 128, 0.34);
        }

        .settings-alert.error {
            background: rgba(248, 113, 113, 0.12);
            color: #fca5a5;
            border: 1px solid rgba(248, 113, 113, 0.34);
        }

        .manage-tabs {
            display: flex;
            gap: 0.6rem;
            margin-bottom: 1.1rem;
            flex-wrap: wrap;
            border-bottom: 1px solid rgba(148, 163, 184, 0.24);
            padding-bottom: 0.5rem;
        }

        .manage-tab-btn {
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.1);
            color: #cbd5e1;
            font-weight: 600;
            padding: 0.55rem 0.85rem;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .manage-tab-btn:hover {
            color: #fff;
            border-color: rgba(249, 115, 22, 0.44);
        }

        .manage-tab-btn.active {
            background: rgba(249, 115, 22, 0.2);
            border-color: rgba(249, 115, 22, 0.52);
            color: #fed7aa;
        }

        .manage-tab-content {
            display: none;
            animation: fadeIn 0.2s ease;
        }

        .manage-tab-content.active {
            display: block;
        }

        .add-email-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.72rem;
            align-items: flex-end;
            border-radius: 14px;
            padding: 0.95rem;
            margin-bottom: 1rem;
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .add-email-form .field {
            flex: 1 1 210px;
        }

        .add-email-form .field label,
        .form-group label {
            display: block;
            margin-bottom: 0.36rem;
            color: #e2e8f0;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .add-email-form .field label i,
        .form-group label i,
        .empty-managers i,
        .manager-item .email i,
        .hint i {
            color: var(--accent);
        }

        .add-email-form .field input,
        .form-group input {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 12px;
            padding: 0.78rem 0.9rem;
            background: var(--field-bg);
            color: var(--text-main);
            font-size: 0.95rem;
            transition: border-color 0.22s ease, box-shadow 0.22s ease;
        }

        .add-email-form .field input:focus,
        .form-group input:focus {
            outline: none;
            border-color: rgba(249, 115, 22, 0.86);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14);
        }

        .add-email-form .field input::placeholder,
        .form-group input::placeholder {
            color: #6b7c95;
        }

        .add-email-form .btn-add,
        .btn-submit {
            border: 1px solid transparent;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #111827;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .add-email-form .btn-add {
            padding: 0.78rem 1.15rem;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn-submit {
            padding: 0.8rem 1.2rem;
            font-size: 0.94rem;
            margin-top: 0.2rem;
        }

        .add-email-form .btn-add:hover,
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(249, 115, 22, 0.3);
        }

        .manager-list {
            margin-top: 0.6rem;
        }

        .manager-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
            padding: 0.68rem 0.85rem;
            border-radius: 12px;
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.2);
            flex-wrap: wrap;
        }

        .manager-item .email {
            color: #f1f5f9;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.89rem;
        }

        .manager-item .role {
            border-radius: 999px;
            padding: 0.18rem 0.72rem;
            font-size: 0.74rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .manager-item .role.owner {
            color: #fdba74;
            background: rgba(249, 115, 22, 0.16);
            border-color: rgba(249, 115, 22, 0.34);
        }

        .manager-item .role.manager {
            color: #cbd5e1;
            background: rgba(148, 163, 184, 0.16);
            border-color: rgba(148, 163, 184, 0.25);
        }

        .manager-item .actions {
            display: flex;
            align-items: center;
        }

        .manager-item .remove-btn {
            border: 1px solid rgba(248, 113, 113, 0.34);
            background: rgba(248, 113, 113, 0.1);
            color: #fca5a5;
            border-radius: 999px;
            width: 32px;
            height: 32px;
            font-size: 0.84rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .manager-item .remove-btn:hover:not(:disabled) {
            background: rgba(248, 113, 113, 0.2);
            color: #fee2e2;
        }

        .manager-item .remove-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .empty-managers {
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 0.2rem 0;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .hint {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            margin-top: 0.35rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .btn-submit:disabled {
            opacity: 0.62;
            pointer-events: none;
        }

        .back-to-profile {
            margin-top: 1.3rem;
            display: inline-flex;
            align-items: center;
            gap: 0.36rem;
            color: #fdba74;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .back-to-profile:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .page-wrapper {
                padding-top: 2rem;
            }

            .profile-header,
            .settings-card {
                border-radius: 20px;
            }
        }

        @media (max-width: 640px) {
            .page-wrapper {
                min-height: auto;
                padding: 1rem 0.75rem 2.2rem;
            }

            .profile-header {
                padding: 1rem;
                gap: 0.75rem;
            }

            .settings-card {
                padding: 1.2rem 1rem 1.35rem;
            }

            .settings-card h2 {
                font-size: 1.35rem;
            }

            .settings-card .sub {
                padding-left: 0;
                font-size: 0.84rem;
            }

            .manage-tabs {
                gap: 0.45rem;
            }

            .manage-tab-btn {
                font-size: 0.8rem;
                padding: 0.5rem 0.72rem;
            }

            .add-email-form {
                flex-direction: column;
                align-items: stretch;
            }

            .add-email-form .btn-add,
            .btn-submit {
                width: 100%;
            }

            .manager-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .manager-item .actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>

<!-- Main content wrapper - centers the form vertically and horizontally -->
<div class="page-wrapper">
    <div class="container">
        <!-- profile header -->
        <div class="profile-header">
            <div class="avatar"><i class="fas fa-users"></i></div>
            <div class="info">
                <h3>
                    <span id="accountDisplayName"><?php echo htmlspecialchars($society_name); ?></span>
                    <span class="badge" id="accountTypeBadge">Society</span>
                </h3>
                <p class="account-email-line"><i class="fas fa-envelope"></i> <span id="accountEmailDisplay"><?php echo htmlspecialchars($society['email'] ?? ''); ?></span></p>
            </div>
        </div>

        <!-- settings card -->
        <div class="settings-card">
            <h2><i class="fas fa-sliders-h"></i> Settings &amp; Privacy</h2>
            <div class="sub">Manage your account preferences and access.</div>

            <?php if (!empty($success)): ?>
                <div class="settings-alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="settings-alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- manage account button -->
            <div class="manage-btn-wrapper">
                <button id="manageAccountBtn" class="btn-manage">
                    <i class="fas fa-user-cog"></i> Manage Account
                </button>
            </div>

            <!-- ===== MANAGE ACCOUNT PANEL ===== -->
            <div id="managePanel" class="manage-panel">
                <h3><i class="fas fa-user-shield"></i> Account Management</h3>
                <div class="desc">
                    Add and manage society account managers.
                </div>

                <!-- Tab Navigation -->
                <div class="manage-tabs">
                    <button type="button" class="manage-tab-btn active" data-tab="tab-managers">
                        <i class="fas fa-users"></i> Managers
                    </button>
                </div>

                <!-- Tab 1: Managers -->
                <div id="tab-managers" class="manage-tab-content active">
                    <div class="desc compact">
                        Add an email address to grant <strong class="highlight-accent">full management access</strong> to this society account.
                        The owner of that email can log in and manage the society profile.
                    </div>

                    <!-- add email form -->
                    <form method="POST" action="manage_account.php" id="addManagerForm">
                        <input type="hidden" name="action" value="add_manager" />
                        <div class="add-email-form">
                            <div class="field">
                                <label for="managerEmailInput"><i class="fas fa-envelope"></i> Manager Email</label>
                                <input type="email" id="managerEmailInput" name="manager_email" placeholder="manager@example.lk" required />
                            </div>
                            <button type="submit" class="btn-add" id="addManagerBtn">
                                <i class="fas fa-plus-circle"></i> Add Email
                            </button>
                        </div>
                    </form>

                    <!-- manager list -->
                    <div class="manager-list" id="managerListContainer">
                        <?php if (empty($managers)): ?>
                            <div class="empty-managers">
                                <i class="fas fa-info-circle"></i> No managers yet. Add an email above.
                            </div>
                        <?php else: ?>
                            <?php foreach ($managers as $manager): ?>
                                <div class="manager-item">
                                    <span class="email">
                                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($manager['email']); ?>
                                    </span>
                                    <span class="role <?php echo $manager['role']; ?>">
                                        <?php echo ucfirst($manager['role']); ?>
                                    </span>
                                    <div class="actions">
                                        <?php if ($manager['role'] !== 'owner'): ?>
                                            <form method="POST" action="manage_account.php" style="display: inline;">
                                                <input type="hidden" name="action" value="remove_manager" />
                                                <input type="hidden" name="manager_id" value="<?php echo $manager['id']; ?>" />
                                                <button type="submit" class="remove-btn" title="Remove access">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="remove-btn" disabled title="Cannot remove owner">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- Back to Society Dashboard - Changed from my_profile.php to society_dashboard.php -->
            <a href="society_dashboard.php" class="back-to-profile">
                <i class="fas fa-arrow-left"></i> Back to Society Dashboard
            </a>
        </div>
    </div>
</div>

<script>
    (function() {
        // DOM refs
        const manageBtn = document.getElementById('manageAccountBtn');
        const managePanel = document.getElementById('managePanel');
        const tabButtons = document.querySelectorAll('.manage-tab-btn');
        const tabContents = document.querySelectorAll('.manage-tab-content');

        function activateTab(tabId) {
            tabButtons.forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
            });
            tabContents.forEach(content => {
                content.classList.toggle('active', content.id === tabId);
            });
        }

        // Manage Account button: toggle panel
        manageBtn.addEventListener('click', function() {
            const isOpen = managePanel.classList.toggle('open');
            if (isOpen) {
                // scroll into view
                managePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        // Tab switching functionality
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                activateTab(tabId);
            });
        });

        // Open a specific tab when requested from header dropdown links.
        const urlParams = new URLSearchParams(window.location.search);
        const requestedTab = urlParams.get('tab');
        const requestedPanel = urlParams.get('panel');
        const tabMap = {
            managers: 'tab-managers'
        };

        if (requestedPanel === '1' || tabMap[requestedTab]) {
            managePanel.classList.add('open');
        }

        if (tabMap[requestedTab]) {
            activateTab(tabMap[requestedTab]);
        }

        // Auto-open panel if there are any alerts (success/error)
        <?php if (!empty($success) || !empty($error)): ?>
            managePanel.classList.add('open');
            managePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        <?php endif; ?>
    })();
</script>
</body>
</html>