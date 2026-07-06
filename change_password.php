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

$extra_stylesheets = ['assets/css/change-password.css'];
$page_title = 'Change Password - CampusHub';

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

// Handle form submission for password change
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
    } elseif (!password_verify($current_password, $current_user['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        try {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt->execute([$hashed_password, $user_id])) {
                $success = 'Password changed successfully!';
                // Refresh current user data
                $stmt = $pdo->prepare("SELECT email, password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to update password. Please try again.';
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
            max-width: 620px;
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

        /* ----- form elements ----- */
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

        .hint {
            display: block;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.3rem;
            padding-left: 0.3rem;
        }

        .hint i {
            margin-right: 0.25rem;
            font-size: 0.7rem;
            color: #f97316;
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

        /* ----- buttons & actions ----- */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 0.6rem;
        }

        .btn {
            font-weight: 600;
            font-size: 1rem;
            padding: 0.9rem 1.8rem;
            border: none;
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

        .btn-primary {
            background: #f97316;
            color: #0a0e17;
            box-shadow: 0 6px 14px rgba(249, 115, 22, 0.2);
        }

        .btn-primary:hover {
            background: #fb923c;
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.01);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn:disabled {
            opacity: 0.6;
            pointer-events: none;
            filter: grayscale(0.3);
        }

        /* ----- forgot password link ----- */
        .forgot-link-wrapper {
            margin-top: 1.2rem;
            text-align: right;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 1.2rem;
        }

        .forgot-link-wrapper a {
            color: #f97316;
            font-weight: 500;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: 0.15s;
        }

        .forgot-link-wrapper a:hover {
            color: #fb923c;
            text-decoration: underline;
        }

        /* ----- status message ----- */
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

        /* ----- forgot-password section (hidden by default) ----- */
        .forgot-section {
            display: none;
            margin-top: 1.8rem;
            padding-top: 1.5rem;
            border-top: 2px dashed rgba(255, 255, 255, 0.06);
        }

        .forgot-section.open {
            display: block;
        }

        .forgot-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.3rem;
        }

        .forgot-section .sub-hint {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 1.2rem;
        }

        .code-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: flex-end;
        }

        .code-row .form-group {
            flex: 2 1 180px;
            margin-bottom: 0;
        }

        .code-row .btn {
            flex: 0 1 auto;
            min-width: 100px;
            padding: 0.7rem 1.5rem;
        }

        .small-btn {
            font-size: 0.9rem;
            padding: 0.6rem 1.2rem;
        }

        .back-to-profile {
            margin-top: 1rem;
            display: inline-block;
            color: #f97316;
            font-weight: 500;
        }

        /* Alert messages */
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
            .action-row {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
            .code-row {
                flex-direction: column;
                align-items: stretch;
            }
            .card-header h2 {
                font-size: 1.4rem;
            }
            .subhead {
                padding-left: 0;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<!-- Main content wrapper - centers the form vertically and horizontally -->
<div class="page-wrapper">
    <div class="settings-page">
        <div class="card">
            <!-- header -->
            <div class="card-header">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <div class="subhead">
                    Choose a new password for your CampusHub account and confirm it with an emailed code.
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="settings-alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="settings-alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- ===== MAIN CHANGE PASSWORD FORM ===== -->
            <form id="changePasswordForm" method="POST" action="change_password.php" novalidate>
                <input type="hidden" name="action" value="change_password" />
                <!-- Current Password -->
                <div class="form-group">
                    <label for="currentPassword"><i class="fas fa-lock"></i> Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="currentPassword" name="current_password" placeholder="Enter your current password" autocomplete="current-password" required />
                    </div>
                </div>

                <!-- New Password -->
                <div class="form-group">
                    <label for="newPassword"><i class="fas fa-pen"></i> New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="newPassword" name="new_password" placeholder="Create a new password" autocomplete="new-password" required />
                        <span class="hint"><i class="fas fa-shield-alt"></i> Use at least 8 characters for a stronger password.</span>
                    </div>
                    <div id="newPasswordError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                </div>

                <!-- Confirm New Password -->
                <div class="form-group">
                    <label for="confirmPassword"><i class="fas fa-check-double"></i> Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter the new password" autocomplete="new-password" required />
                    </div>
                    <div id="confirmPasswordError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                </div>

                <!-- buttons -->
                <div class="action-row">
                    <button type="submit" class="btn btn-primary" id="updatePwBtn">
                        <i class="fas fa-pen-to-square"></i> Update Password
                    </button>
                    <a href="my_profile.php" class="btn btn-secondary" id="backToProfileBtn">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>

                <!-- status message -->
                <div id="statusMessage" class="status-msg">
                    <i class="fas fa-circle-check"></i> <span id="statusText"></span>
                </div>
            </form>

            <!-- ===== FORGOT PASSWORD LINK (under the form) ===== -->
            <div class="forgot-link-wrapper">
                <a href="#" id="forgotPasswordToggle">
                    <i class="fas fa-question-circle"></i> Forgot your current password?
                </a>
            </div>

            <!-- ===== FORGOT PASSWORD SECTION (collapsible) ===== -->
            <div id="forgotSection" class="forgot-section">
                <h3><i class="fas fa-envelope"></i> Reset via Email</h3>
                <div class="sub-hint">
                    We'll send a verification code to your registered email. Enter the code to verify your identity, then set a new password.
                </div>

                <!-- step 1: send code -->
                <div id="forgotStep1">
                    <div class="form-group">
                        <label for="forgotEmail"><i class="fas fa-envelope"></i> Your registered email</label>
                        <div class="input-wrapper">
                            <input type="email" id="forgotEmail" placeholder="name@example.lk" value="<?php echo htmlspecialchars($current_user['email']); ?>" />
                        </div>
                        <div id="forgotEmailError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>
                    <button type="button" class="btn btn-primary small-btn" id="sendCodeBtn">
                        <i class="fas fa-paper-plane"></i> Send Code
                    </button>
                    <div id="codeSentStatus" class="status-msg" style="margin-top: 0.8rem;">
                        <i class="fas fa-circle-check"></i> <span></span>
                    </div>
                </div>

                <!-- step 2: verify code + new password (hidden initially) -->
                <div id="forgotStep2" style="display: none; margin-top: 1.2rem;">
                    <div class="form-group">
                        <label for="verificationCode"><i class="fas fa-shield-alt"></i> Verification Code</label>
                        <div class="input-wrapper">
                            <input type="text" id="verificationCode" placeholder="Enter the 6-digit code" maxlength="6" />
                        </div>
                        <div id="codeError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>

                    <div class="form-group">
                        <label for="newPwForgot"><i class="fas fa-pen"></i> New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="newPwForgot" placeholder="New password (min 8 chars)" />
                        </div>
                        <div id="newPwForgotError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPwForgot"><i class="fas fa-check-double"></i> Confirm New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirmPwForgot" placeholder="Re-enter new password" />
                        </div>
                        <div id="confirmPwForgotError" class="error-message"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>

                    <button type="button" class="btn btn-primary" id="resetPwBtn">
                        <i class="fas fa-check-circle"></i> Reset Password
                    </button>
                    <div id="resetStatus" class="status-msg" style="margin-top: 0.8rem;">
                        <i class="fas fa-circle-check"></i> <span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT -->
<script>
    (function() {
        // ---------- DOM refs ----------
        const form = document.getElementById('changePasswordForm');
        const currentPw = document.getElementById('currentPassword');
        const newPw = document.getElementById('newPassword');
        const confirmPw = document.getElementById('confirmPassword');
        const newPwError = document.getElementById('newPasswordError');
        const confirmPwError = document.getElementById('confirmPasswordError');
        const statusMsg = document.getElementById('statusMessage');
        const statusText = document.getElementById('statusText');
        const updateBtn = document.getElementById('updatePwBtn');
        const backBtn = document.getElementById('backToProfileBtn');

        // forgot elements
        const forgotToggle = document.getElementById('forgotPasswordToggle');
        const forgotSection = document.getElementById('forgotSection');
        const forgotEmail = document.getElementById('forgotEmail');
        const forgotEmailError = document.getElementById('forgotEmailError');
        const sendCodeBtn = document.getElementById('sendCodeBtn');
        const codeSentStatus = document.getElementById('codeSentStatus');
        const step1 = document.getElementById('forgotStep1');
        const step2 = document.getElementById('forgotStep2');
        const verifCode = document.getElementById('verificationCode');
        const codeError = document.getElementById('codeError');
        const newPwForgot = document.getElementById('newPwForgot');
        const confirmPwForgot = document.getElementById('confirmPwForgot');
        const newPwForgotError = document.getElementById('newPwForgotError');
        const confirmPwForgotError = document.getElementById('confirmPwForgotError');
        const resetPwBtn = document.getElementById('resetPwBtn');
        const resetStatus = document.getElementById('resetStatus');

        // ---------- helpers ----------
        function showStatus(msg, isError = false, container = statusMsg, textEl = statusText) {
            container.classList.add('show');
            if (isError) {
                container.classList.add('error');
                container.querySelector('i').className = 'fas fa-circle-exclamation';
            } else {
                container.classList.remove('error');
                container.querySelector('i').className = 'fas fa-circle-check';
            }
            textEl.textContent = msg;
        }

        function hideStatus(container = statusMsg) {
            container.classList.remove('show', 'error');
        }

        function clearInlineErrors() {
            newPwError.classList.remove('visible');
            confirmPwError.classList.remove('visible');
            newPwError.querySelector('span').textContent = '';
            confirmPwError.querySelector('span').textContent = '';
            forgotEmailError.classList.remove('visible');
            codeError.classList.remove('visible');
            newPwForgotError.classList.remove('visible');
            confirmPwForgotError.classList.remove('visible');
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // ---------- validate main form ----------
        function validateMainForm() {
            clearInlineErrors();
            hideStatus();
            let valid = true;

            // current password: non-empty
            if (!currentPw.value.trim()) {
                showStatus('Please enter your current password.', true);
                valid = false;
            }

            // new password: min 8 chars
            const newVal = newPw.value;
            if (newVal.length < 8) {
                newPwError.querySelector('span').textContent = 'Password must be at least 8 characters.';
                newPwError.classList.add('visible');
                valid = false;
            }

            // confirm: match
            if (confirmPw.value !== newVal) {
                confirmPwError.querySelector('span').textContent = 'Passwords do not match.';
                confirmPwError.classList.add('visible');
                valid = false;
            } else if (confirmPw.value && confirmPw.value.length < 8) {
                confirmPwError.querySelector('span').textContent = 'Password must be at least 8 characters.';
                confirmPwError.classList.add('visible');
                valid = false;
            }

            // if current password empty and other fields ok, we already show status
            if (!currentPw.value.trim() && valid) {
                showStatus('Current password is required.', true);
                valid = false;
            }

            return valid;
        }

        // ---------- main form submit ----------
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateMainForm()) return;

            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', currentPw.value.trim());
            formData.append('new_password', newPw.value.trim());
            formData.append('confirm_password', confirmPw.value.trim());

            try {
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                // Check if the page reloaded with success message
                if (text.includes('settings-alert success')) {
                    // Password was changed successfully
                    showStatus('Password changed successfully!', false);
                    currentPw.value = '';
                    newPw.value = '';
                    confirmPw.value = '';
                    clearInlineErrors();
                    // Reload page after a moment to show updated state
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else if (text.includes('settings-alert error')) {
                    // Extract error message from response
                    const errorMatch = text.match(/settings-alert error">([^<]+)</);
                    const errorMsg = errorMatch ? errorMatch[1] : 'Failed to change password.';
                    showStatus(errorMsg, true);
                } else {
                    showStatus('Something went wrong. Please try again.', true);
                }
            } catch (err) {
                console.error('Error:', err);
                showStatus('Network error. Please try again.', true);
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fas fa-pen-to-square"></i> Update Password';
            }
        });

        // ---------- Back to Profile ----------
        backBtn.addEventListener('click', function(e) {
            // Let the link handle navigation
        });

        // ---------- Forgot password toggle ----------
        forgotToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isOpen = forgotSection.classList.toggle('open');
            if (isOpen) {
                forgotToggle.innerHTML = '<i class="fas fa-minus-circle"></i> Hide forgot password';
                // reset forgot states
                step2.style.display = 'none';
                step1.style.display = 'block';
                hideStatus(codeSentStatus);
                hideStatus(resetStatus);
                clearInlineErrors();
                verifCode.value = '';
                newPwForgot.value = '';
                confirmPwForgot.value = '';
            } else {
                forgotToggle.innerHTML = '<i class="fas fa-question-circle"></i> Forgot your current password?';
                forgotSection.classList.remove('open');
            }
        });

        // ---------- Send code (simulate) ----------
        sendCodeBtn.addEventListener('click', function() {
            const email = forgotEmail.value.trim();
            forgotEmailError.classList.remove('visible');
            hideStatus(codeSentStatus);

            if (!email || !isValidEmail(email)) {
                forgotEmailError.querySelector('span').textContent = 'Please enter a valid email address.';
                forgotEmailError.classList.add('visible');
                return;
            }

            // simulate sending code
            sendCodeBtn.disabled = true;
            sendCodeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            setTimeout(() => {
                sendCodeBtn.disabled = false;
                sendCodeBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Code';
                showStatus('Verification code sent to your email! (demo: use "123456")', false, codeSentStatus, codeSentStatus.querySelector('span'));
                // show step 2
                step2.style.display = 'block';
                // store a fake code in session (for demo)
                sessionStorage.setItem('demoResetCode', '123456');
                sessionStorage.setItem('demoResetEmail', email);
            }, 1200);
        });

        // ---------- Reset password via code ----------
        resetPwBtn.addEventListener('click', function() {
            // validate
            let valid = true;
            const code = verifCode.value.trim();
            const newP = newPwForgot.value;
            const confirmP = confirmPwForgot.value;

            codeError.classList.remove('visible');
            newPwForgotError.classList.remove('visible');
            confirmPwForgotError.classList.remove('visible');
            hideStatus(resetStatus);

            if (code !== '123456') {
                codeError.querySelector('span').textContent = 'Invalid code. Please use "123456" (demo).';
                codeError.classList.add('visible');
                valid = false;
            }

            if (newP.length < 8) {
                newPwForgotError.querySelector('span').textContent = 'Password must be at least 8 characters.';
                newPwForgotError.classList.add('visible');
                valid = false;
            }

            if (newP !== confirmP) {
                confirmPwForgotError.querySelector('span').textContent = 'Passwords do not match.';
                confirmPwForgotError.classList.add('visible');
                valid = false;
            } else if (confirmP && confirmP.length < 8) {
                confirmPwForgotError.querySelector('span').textContent = 'Password must be at least 8 characters.';
                confirmPwForgotError.classList.add('visible');
                valid = false;
            }

            if (!valid) return;

            // simulate reset
            resetPwBtn.disabled = true;
            resetPwBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';

            setTimeout(() => {
                resetPwBtn.disabled = false;
                resetPwBtn.innerHTML = '<i class="fas fa-check-circle"></i> Reset Password';
                showStatus('Password reset successfully! You can now log in with your new password.', false, resetStatus, resetStatus.querySelector('span'));
                // clear fields
                verifCode.value = '';
                newPwForgot.value = '';
                confirmPwForgot.value = '';
                // close forgot section after a moment
                setTimeout(() => {
                    forgotToggle.click();
                }, 2500);
            }, 1500);
        });

        // close status on click
        document.querySelectorAll('.status-msg').forEach(el => {
            el.addEventListener('click', function() { this.classList.remove('show', 'error'); });
        });

        // initial hide
        hideStatus();
        hideStatus(codeSentStatus);
        hideStatus(resetStatus);
    })();
</script>
</body>
</html>