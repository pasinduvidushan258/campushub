<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/security_email.php';
require_once 'includes/notification_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$extra_stylesheets = ['assets/css/change-email.css'];
$page_title = 'Change Login Email - CampusHub';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$stmt = $pdo->prepare("SELECT email, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit();
}

// Handle form submission for email change
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
    } elseif ($new_email === $current_user['email']) {
        $error = 'New email must be different from your current email.';
    } elseif (!password_verify($current_password, $current_user['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        try {
            // Check if email already exists in database
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$new_email, $user_id]);
            
            if ($check_stmt->fetch()) {
                $error = 'This email is already registered. Please use a different email.';
            } else {
                // Update email in database
                $update_stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                if ($update_stmt->execute([$new_email, $user_id])) {
                    $success = 'Email updated successfully!';

                    campushub_notify_user($pdo, [
                        'recipient_user_id' => $user_id,
                        'actor_user_id' => $user_id,
                        'type' => 'account_email_changed',
                        'title' => 'Your account email was changed',
                        'message' => 'Your login email was updated to ' . $new_email . '.',
                        'entity_type' => 'account',
                        'entity_id' => $user_id,
                        'link_url' => 'change_email.php',
                        'dedupe_key' => 'email-change:' . $user_id . ':' . date('YmdHis'),
                    ]);

                    // Refresh current user data
                    $stmt = $pdo->prepare("SELECT email, password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Failed to update email. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $page_title; ?></title>
    <!-- Font Awesome for icons -->
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
            padding: 2.4rem 1.2rem 3rem;
        }

        .settings-page {
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
        }

        .card {
            width: 100%;
            background: linear-gradient(160deg, rgba(19, 33, 61, 0.92), rgba(11, 21, 40, 0.9));
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            padding: 2.35rem 2.15rem 2.4rem;
            box-shadow:
                0 28px 70px rgba(2, 6, 23, 0.72),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -100px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle at center, rgba(249, 115, 22, 0.25), transparent 68%);
            pointer-events: none;
        }

        .card-header {
            position: relative;
            margin-bottom: 1.7rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }

        .card-header h2 {
            font-size: clamp(1.5rem, 2.2vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .card-header h2 i {
            color: var(--accent);
            font-size: 1.4rem;
            filter: drop-shadow(0 8px 14px rgba(249, 115, 22, 0.35));
        }

        .subhead {
            margin-top: 0.55rem;
            padding-left: 2.1rem;
            max-width: 540px;
            line-height: 1.62;
            font-size: 0.93rem;
            color: var(--text-muted);
        }

        .current-email-box {
            border-radius: 14px;
            padding: 0.9rem 1rem;
            margin: 1.1rem 0 1.6rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.42rem 0.62rem;
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.25);
        }

        .current-email-box span.label {
            font-weight: 600;
            color: #e2e8f0;
            font-size: 0.92rem;
        }

        .current-email-box span.label i {
            color: var(--accent);
            margin-right: 0.38rem;
        }

        .current-email-box .email {
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 999px;
            padding: 0.2rem 0.9rem;
            color: var(--text-main);
            background: rgba(148, 163, 184, 0.17);
            border: 1px solid rgba(148, 163, 184, 0.24);
            word-break: break-all;
        }

        .form-group {
            margin-bottom: 1.35rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.94rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: #e2e8f0;
        }

        .form-group label i {
            color: var(--accent);
            width: 1.25rem;
            margin-right: 0.35rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 14px;
            padding: 0.92rem 1rem;
            font-size: 1rem;
            line-height: 1.4;
            color: var(--text-main);
            background: var(--field-bg);
            transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.2s ease;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: rgba(249, 115, 22, 0.88);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14);
            transform: translateY(-1px);
        }

        .input-wrapper input::placeholder {
            color: #6b7c95;
            font-weight: 500;
        }

        .input-wrapper .hint {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.45rem;
            padding-left: 0.22rem;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .input-wrapper .hint i {
            font-size: 0.8rem;
            color: var(--accent);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.9rem;
        }

        .error-message {
            margin-top: 0.38rem;
            padding-left: 0.24rem;
            color: var(--danger);
            font-size: 0.81rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            opacity: 0;
            transform: translateY(-2px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .error-message.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .btn-update {
            width: 100%;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 0.9rem 1rem;
            margin-top: 0.2rem;
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            cursor: pointer;
            letter-spacing: 0.01em;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.55rem;
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.26);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .btn-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(249, 115, 22, 0.33);
        }

        .btn-update:active {
            transform: translateY(1px);
        }

        .btn-update:disabled {
            opacity: 0.62;
            pointer-events: none;
        }

        .action-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.82rem;
            margin-top: 0.5rem;
        }

        .btn-secondary {
            width: 100%;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-weight: 700;
            color: #e2e8f0;
            background: rgba(148, 163, 184, 0.12);
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            transition: transform 0.22s ease, border-color 0.22s ease, background 0.22s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            border-color: rgba(249, 115, 22, 0.46);
            background: rgba(148, 163, 184, 0.2);
        }

        .status-msg {
            margin-top: 1.3rem;
            border-radius: 13px;
            padding: 0.82rem 0.94rem;
            border: 1px solid rgba(34, 197, 94, 0.24);
            background: rgba(34, 197, 94, 0.11);
            color: var(--success);
            font-size: 0.9rem;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 0.55rem;
        }

        .status-msg.show {
            display: flex;
        }

        .status-msg.error {
            color: var(--danger);
            border-color: rgba(248, 113, 113, 0.3);
            background: rgba(248, 113, 113, 0.1);
        }

        .settings-alert {
            margin-bottom: 1.3rem;
            border-radius: 13px;
            padding: 0.82rem 0.96rem;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.5;
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

        @media (max-width: 900px) {
            .page-wrapper {
                padding-top: 2rem;
            }

            .card {
                border-radius: 22px;
                padding: 2rem 1.5rem 2.1rem;
            }
        }

        @media (max-width: 640px) {
            .page-wrapper {
                min-height: auto;
                padding: 1.2rem 0.85rem 2.4rem;
            }

            .card {
                border-radius: 18px;
                padding: 1.45rem 1rem 1.6rem;
            }

            .card-header {
                margin-bottom: 1.3rem;
                padding-bottom: 1rem;
            }

            .card-header h2 {
                font-size: 1.38rem;
            }

            .subhead {
                padding-left: 0;
                font-size: 0.85rem;
            }

            .current-email-box {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-row,
            .action-row {
                grid-template-columns: 1fr;
            }

            .btn-secondary,
            .btn-update {
                padding-block: 0.86rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

<!-- Main content wrapper - centers the form vertically and horizontally -->
<div class="page-wrapper">
    <div class="settings-page">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-envelope"></i> Change Login Email</h2>
                <div class="subhead">
                    This becomes the email you use to log in to CampusHub.
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="settings-alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="settings-alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- current email display -->
            <div class="current-email-box">
                <span class="label"><i class="fas fa-user-check"></i>Current login email:</span>
                <span class="email" id="currentEmailDisplay"><?php echo htmlspecialchars($current_user['email']); ?></span>
            </div>

            <!-- form -->
            <form id="changeEmailForm" method="POST" action="change_email.php" novalidate>
                <input type="hidden" name="action" value="change_email" />
                <!-- Current Password -->
                <div class="form-group">
                    <label for="currentPassword"><i class="fas fa-lock"></i> Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="currentPassword" name="current_password" placeholder="Enter your current password" autocomplete="current-password" required />
                    </div>
                </div>

                <!-- new email & confirm row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="newEmail"><i class="fas fa-envelope-open-text"></i> New Login Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="newEmail" name="new_email" placeholder="name@example.lk" autocomplete="off" required />
                            <span class="hint"><i class="fas fa-check-circle"></i> Use a valid email address ending with .lk</span>
                        </div>
                        <div id="newEmailError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmEmail"><i class="fas fa-check-double"></i> Confirm New Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="confirmEmail" name="confirm_email" placeholder="Re-enter the new email" autocomplete="off" required />
                        </div>
                        <div id="confirmEmailError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>
                </div>

                <!-- buttons -->
                <div class="action-row">
                    <button type="submit" class="btn-update" id="updateBtn">
                        <i class="fas fa-pen-to-square"></i> Update Login Email
                    </button>
                    <a href="my_profile.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>

                <!-- status message -->
                <div id="statusMessage" class="status-msg">
                    <i class="fas fa-circle-check"></i> <span id="statusText"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====== JavaScript ====== -->
<script>
    (function() {
        const form = document.getElementById('changeEmailForm');
        const currentPassword = document.getElementById('currentPassword');
        const newEmail = document.getElementById('newEmail');
        const confirmEmail = document.getElementById('confirmEmail');
        const newEmailError = document.getElementById('newEmailError');
        const confirmEmailError = document.getElementById('confirmEmailError');
        const statusMsg = document.getElementById('statusMessage');
        const statusText = document.getElementById('statusText');
        const updateBtn = document.getElementById('updateBtn');
        const currentEmailDisplay = document.getElementById('currentEmailDisplay');

        // helper to show status
        function showStatus(message, isError = false) {
            statusMsg.classList.add('show');
            if (isError) {
                statusMsg.classList.add('error');
                statusMsg.querySelector('i').className = 'fas fa-circle-exclamation';
            } else {
                statusMsg.classList.remove('error');
                statusMsg.querySelector('i').className = 'fas fa-circle-check';
            }
            statusText.textContent = message;
        }

        function hideStatus() {
            statusMsg.classList.remove('show', 'error');
        }

        // reset inline errors
        function clearInlineErrors() {
            newEmailError.classList.remove('visible');
            confirmEmailError.classList.remove('visible');
            newEmailError.querySelector('span').textContent = '';
            confirmEmailError.querySelector('span').textContent = '';
        }

        function isValidEmailWithLk(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // validation (returns true if valid)
        function validateForm() {
            clearInlineErrors();
            hideStatus();

            let valid = true;

            // 1. current password: non-empty
            if (!currentPassword.value.trim()) {
                showStatus('Please enter your current password.', true);
                valid = false;
            }

            // 2. new email: required + valid format
            const newEmailVal = newEmail.value.trim();
            if (!newEmailVal) {
                newEmailError.querySelector('span').textContent = 'New email is required.';
                newEmailError.classList.add('visible');
                valid = false;
            } else if (!isValidEmailWithLk(newEmailVal)) {
                newEmailError.querySelector('span').textContent = 'Must be a valid email address.';
                newEmailError.classList.add('visible');
                valid = false;
            }

            // 3. confirm email: match + non-empty
            const confirmVal = confirmEmail.value.trim();
            if (!confirmVal) {
                confirmEmailError.querySelector('span').textContent = 'Please confirm your new email.';
                confirmEmailError.classList.add('visible');
                valid = false;
            } else if (newEmailVal && confirmVal !== newEmailVal) {
                confirmEmailError.querySelector('span').textContent = 'Emails do not match.';
                confirmEmailError.classList.add('visible');
                valid = false;
            } else if (newEmailVal && confirmVal === newEmailVal && !isValidEmailWithLk(confirmVal)) {
                confirmEmailError.querySelector('span').textContent = 'Must be a valid email.';
                confirmEmailError.classList.add('visible');
                valid = false;
            }

            if (!currentPassword.value.trim() && valid) {
                showStatus('Current password is required.', true);
                valid = false;
            }

            return valid;
        }

        // --- handle form submit ---
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateForm()) {
                return;
            }

            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            const formData = new FormData();
            formData.append('action', 'change_email');
            formData.append('current_password', currentPassword.value.trim());
            formData.append('new_email', newEmail.value.trim());
            formData.append('confirm_email', confirmEmail.value.trim());

            try {
                const response = await fetch('change_email.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                // Check if the page reloaded with success message
                if (text.includes('settings-alert success')) {
                    showStatus('Email updated successfully!', false);
                    currentEmailDisplay.textContent = newEmail.value.trim();
                    currentPassword.value = '';
                    newEmail.value = '';
                    confirmEmail.value = '';
                    clearInlineErrors();
                    // Reload page after a moment to show updated state
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else if (text.includes('settings-alert error')) {
                    // Extract error message from response
                    const errorMatch = text.match(/settings-alert error">([^<]+)</);
                    const errorMsg = errorMatch ? errorMatch[1] : 'Failed to update email.';
                    showStatus(errorMsg, true);
                } else {
                    showStatus('Something went wrong. Please try again.', true);
                }
            } catch (err) {
                console.error('Error:', err);
                showStatus('Network error. Please try again.', true);
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fas fa-pen-to-square"></i> Update Login Email';
            }
        });

        // real-time validation hints
        newEmail.addEventListener('input', function() {
            const val = this.value.trim();
            if (val && !isValidEmailWithLk(val)) {
                newEmailError.querySelector('span').textContent = 'Must be a valid email address.';
                newEmailError.classList.add('visible');
            } else {
                newEmailError.classList.remove('visible');
            }
            if (confirmEmail.value.trim()) {
                if (confirmEmail.value.trim() !== val) {
                    confirmEmailError.querySelector('span').textContent = 'Emails do not match.';
                    confirmEmailError.classList.add('visible');
                } else {
                    confirmEmailError.classList.remove('visible');
                }
            }
            hideStatus();
        });

        confirmEmail.addEventListener('input', function() {
            const val = this.value.trim();
            const newVal = newEmail.value.trim();
            if (val && newVal && val !== newVal) {
                confirmEmailError.querySelector('span').textContent = 'Emails do not match.';
                confirmEmailError.classList.add('visible');
            } else if (val && !isValidEmailWithLk(val)) {
                confirmEmailError.querySelector('span').textContent = 'Must be a valid email.';
                confirmEmailError.classList.add('visible');
            } else {
                confirmEmailError.classList.remove('visible');
            }
            hideStatus();
        });

        statusMsg.addEventListener('click', function() {
            this.classList.remove('show', 'error');
        });

        hideStatus();
    })();
</script>
</body>
</html>