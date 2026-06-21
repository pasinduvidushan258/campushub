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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
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

        .settings-page {
            max-width: 560px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            width: 100%;
            background: #141b2b;
            border-radius: 24px;
            padding: 2.25rem 2rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.6);
        }

        .card-header {
            margin-bottom: 1.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 1.2rem;
        }

        .card-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .card-header h2 i {
            color: #f97316;
            font-size: 1.6rem;
        }

        .subhead {
            font-size: 0.95rem;
            color: #94a3b8;
            margin-top: 0.4rem;
            padding-left: 2.4rem;
            line-height: 1.5;
        }

        .current-email-box {
            background: rgba(249, 115, 22, 0.08);
            border-radius: 16px;
            padding: 0.9rem 1.4rem;
            margin: 1.2rem 0 1.8rem 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.4rem 0.6rem;
            border: 1px solid rgba(249, 115, 22, 0.15);
        }

        .current-email-box span.label {
            font-weight: 500;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .current-email-box span.label i {
            color: #f97316;
            margin-right: 0.4rem;
        }

        .current-email-box .email {
            font-weight: 600;
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.06);
            padding: 0.2rem 1rem;
            border-radius: 40px;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            border: 1px solid rgba(255, 255, 255, 0.08);
            word-break: break-all;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            font-size: 0.9rem;
            color: #e2e8f0;
            margin-bottom: 0.4rem;
            letter-spacing: -0.01em;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: #f97316;
            width: 1.1rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: #0d1423;
            transition: 0.2s ease;
            color: #f1f5f9;
            font-weight: 450;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #f97316;
            background: #111a2e;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .input-wrapper input::placeholder {
            color: #64748b;
            font-weight: 350;
            font-size: 0.95rem;
        }

        .input-wrapper .hint {
            display: block;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.3rem;
            padding-left: 0.3rem;
        }

        .input-wrapper .hint i {
            margin-right: 0.25rem;
            font-size: 0.7rem;
            color: #f97316;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem 1.2rem;
        }

        .form-row .form-group {
            flex: 1 1 calc(50% - 0.6rem);
            min-width: 180px;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            padding-left: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .error-message.visible {
            opacity: 1;
        }

        .btn-update {
            background: #f97316;
            color: #0a0e17;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.9rem 1.8rem;
            border: none;
            border-radius: 12px;
            width: 100%;
            margin-top: 0.6rem;
            cursor: pointer;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            letter-spacing: 0.3px;
            box-shadow: 0 6px 14px rgba(249, 115, 22, 0.2);
        }

        .btn-update:hover {
            background: #fb923c;
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .btn-update:active {
            transform: scale(0.98);
        }

        .btn-update:disabled {
            opacity: 0.6;
            pointer-events: none;
            filter: grayscale(0.3);
        }

        .btn-update i {
            font-size: 1rem;
        }

        /* Back button row */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 0.6rem;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 600;
            font-size: 1rem;
            padding: 0.9rem 1.8rem;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            letter-spacing: 0.3px;
            flex: 1 1 auto;
            min-width: 140px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.01);
        }

        .status-msg {
            margin-top: 1.5rem;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .status-msg.show {
            display: flex;
        }

        .status-msg.error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .status-msg i {
            font-size: 1.1rem;
        }

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

        @media (max-width: 480px) {
            .page-wrapper {
                padding: 1rem;
                margin-top: 0;
            }
            .card {
                padding: 1.8rem 1.2rem;
            }
            .form-row .form-group {
                flex: 1 1 100%;
            }
            .current-email-box {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-header h2 {
                font-size: 1.4rem;
            }
            .subhead {
                padding-left: 0;
                font-size: 0.85rem;
            }
            .action-row {
                flex-direction: column;
            }
            .btn-secondary {
                width: 100%;
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
            if (!emailRegex.test(email)) return false;
            return email.toLowerCase().endsWith('.lk');
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

            // 2. new email: required + ends with .lk + valid format
            const newEmailVal = newEmail.value.trim();
            if (!newEmailVal) {
                newEmailError.querySelector('span').textContent = 'New email is required.';
                newEmailError.classList.add('visible');
                valid = false;
            } else if (!isValidEmailWithLk(newEmailVal)) {
                newEmailError.querySelector('span').textContent = 'Must be a valid email ending with .lk';
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
                confirmEmailError.querySelector('span').textContent = 'Must end with .lk';
                confirmEmailError.classList.add('visible');
                valid = false;
            }

            if (!currentPassword.value.trim() && valid) {
                showStatus('Current password is required.', true);
                valid = false;
            }

            return valid;
        }

        // --- Simulate PHP backend via fetch ---
        async function submitToBackend(formData) {
            return new Promise((resolve) => {
                setTimeout(() => {
                    const password = formData.get('current_password');
                    const newEmail = formData.get('new_email');

                    if (password !== 'password123') {
                        resolve({ success: false, message: 'Current password is incorrect.' });
                    } else if (!newEmail.toLowerCase().endsWith('.lk')) {
                        resolve({ success: false, message: 'Email must end with .lk.' });
                    } else {
                        resolve({ success: true, message: `Login email updated to ${newEmail}` });
                    }
                }, 1000);
            });
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
            formData.append('current_password', currentPassword.value.trim());
            formData.append('new_email', newEmail.value.trim());
            formData.append('confirm_email', confirmEmail.value.trim());

            try {
                const response = await submitToBackend(formData);
                if (response.success) {
                    showStatus(response.message, false);
                    currentEmailDisplay.textContent = newEmail.value.trim();
                    currentPassword.value = '';
                    clearInlineErrors();
                } else {
                    showStatus(response.message, true);
                }
            } catch (err) {
                showStatus('Something went wrong. Please try again.', true);
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fas fa-pen-to-square"></i> Update Login Email';
            }
        });

        // real-time validation hints
        newEmail.addEventListener('input', function() {
            const val = this.value.trim();
            if (val && !isValidEmailWithLk(val)) {
                newEmailError.querySelector('span').textContent = 'Must be a valid email ending with .lk';
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
                confirmEmailError.querySelector('span').textContent = 'Must end with .lk';
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