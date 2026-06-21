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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0e17;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Main content wrapper - centers the form vertically and horizontally */
        .page-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            margin-top: -40px; /* Offset to account for header */
        }

        .container {
            max-width: 800px;
            width: 100%;
        }

        /* ---------- profile header ---------- */
        .profile-header {
            background: #141b2b;
            padding: 1.5rem 2rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
            margin-bottom: 1.8rem;
        }

        .profile-header .avatar {
            width: 56px;
            height: 56px;
            background: #f97316;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a0e17;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .profile-header .info h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #f8fafc;
        }

        .profile-header .info p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .badge {
            background: rgba(249, 115, 22, 0.15);
            color: #f97316;
            padding: 0.2rem 1rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-left: 0.4rem;
        }

        /* ---------- settings card ---------- */
        .settings-card {
            background: #141b2b;
            border-radius: 24px;
            padding: 2rem 2rem 2.2rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.4);
        }

        .settings-card h2 {
            font-size: 1.6rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .settings-card h2 i {
            color: #f97316;
        }

        .settings-card .sub {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 1.8rem;
            padding-left: 2.2rem;
        }

        /* ---------- manage account button ---------- */
        .manage-btn-wrapper {
            margin: 1.2rem 0 1rem;
        }

        .btn-manage {
            background: #f97316;
            color: #0a0e17;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
        }

        .btn-manage:hover {
            background: #fb923c;
            transform: scale(1.02);
        }

        /* ---------- manage panel ---------- */
        .manage-panel {
            margin-top: 2rem;
            padding-top: 1.8rem;
            border-top: 2px solid rgba(255, 255, 255, 0.06);
            display: none;
        }

        .manage-panel.open {
            display: block;
        }

        .manage-panel h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .manage-panel h3 i {
            color: #f97316;
        }

        .manage-panel .desc {
            color: #94a3b8;
            font-size: 0.95rem;
            margin: 0.2rem 0 1.2rem 0;
        }

        /* ---------- settings alert ---------- */
        .settings-alert {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .settings-alert.success {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .settings-alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* ---------- form add email ---------- */
        .add-email-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: flex-end;
            background: rgba(255, 255, 255, 0.03);
            padding: 1.2rem 1.4rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 1.5rem;
        }

        .add-email-form .field {
            flex: 2 1 200px;
        }

        .add-email-form .field label {
            display: block;
            font-weight: 500;
            font-size: 0.8rem;
            color: #e2e8f0;
            margin-bottom: 0.2rem;
        }

        .add-email-form .field label i {
            color: #f97316;
            margin-right: 0.3rem;
        }

        .add-email-form .field input {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            font-size: 0.95rem;
            background: #0d1423;
            color: #f1f5f9;
            transition: 0.2s;
        }

        .add-email-form .field input:focus {
            border-color: #f97316;
            outline: none;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .add-email-form .field input::placeholder {
            color: #64748b;
        }

        .add-email-form .btn-add {
            background: #f97316;
            color: #0a0e17;
            border: none;
            padding: 0.6rem 1.8rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-email-form .btn-add:hover {
            background: #fb923c;
        }

        /* ---------- manager list ---------- */
        .manager-list {
            margin-top: 0.8rem;
        }

        .manager-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .manager-item .email {
            font-weight: 500;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .manager-item .email i {
            color: #f97316;
        }

        .manager-item .role {
            font-size: 0.75rem;
            padding: 0.2rem 0.9rem;
            border-radius: 40px;
            font-weight: 500;
        }

        .manager-item .role.owner {
            background: rgba(249, 115, 22, 0.15);
            color: #f97316;
        }

        .manager-item .role.manager {
            background: rgba(255, 255, 255, 0.06);
            color: #94a3b8;
        }

        .manager-item .actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .manager-item .remove-btn {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            transition: 0.15s;
        }

        .manager-item .remove-btn:hover:not(:disabled) {
            background: rgba(239, 68, 68, 0.1);
        }

        .manager-item .remove-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .empty-managers {
            color: #64748b;
            font-size: 0.9rem;
            padding: 0.6rem 0;
        }

        .empty-managers i {
            color: #f97316;
            margin-right: 0.5rem;
        }

        /* ---------- back button ---------- */
        .back-to-profile {
            margin-top: 1.5rem;
            display: inline-block;
            color: #f97316;
            font-weight: 500;
            text-decoration: none;
            transition: 0.15s;
        }

        .back-to-profile:hover {
            color: #fb923c;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .page-wrapper {
                padding: 1rem;
                margin-top: 0;
            }
            .settings-card {
                padding: 1.8rem 1.2rem;
            }
            .add-email-form {
                flex-direction: column;
                align-items: stretch;
            }
            .add-email-form .btn-add {
                align-self: flex-start;
            }
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .settings-card h2 {
                font-size: 1.4rem;
            }
            .settings-card .sub {
                padding-left: 0;
                font-size: 0.85rem;
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
                <p><i class="fas fa-envelope" style="margin-right: 0.4rem; color: #f97316;"></i> <span id="accountEmailDisplay"><?php echo htmlspecialchars($society['email'] ?? ''); ?></span></p>
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
                <h3><i class="fas fa-user-shield"></i> Society Account Management</h3>
                <div class="desc">
                    Add an email address to grant <strong style="color: #f97316;">full management access</strong> to this society account.
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

        // Manage Account button: toggle panel
        manageBtn.addEventListener('click', function() {
            const isOpen = managePanel.classList.toggle('open');
            if (isOpen) {
                // scroll into view
                managePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        // Auto-open panel if there are any alerts (success/error)
        <?php if (!empty($success) || !empty($error)): ?>
            managePanel.classList.add('open');
            managePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        <?php endif; ?>
    })();
</script>
</body>
</html>