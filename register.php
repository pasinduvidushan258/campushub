<?php
// Load the database connection. $pdo object becomes available after this.
require 'config/database.php';

// Import PHPMailer classes into the global namespace.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Manually load the three required PHPMailer source files from the local folder.
// These are loaded directly instead of using Composer autoload.
require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

$error = "";
$success = "";

// Only process registration logic when the form is submitted via POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize all incoming text fields with trim() to remove accidental whitespace.
    $fullname  = trim($_POST['fullname']);
    $university = trim($_POST['university']);
    $category  = trim($_POST['category']);
    $password  = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // student_number is only applicable to students, so default to NULL for lecturers.
    $student_number = NULL;
    $email = "";

    // Assign the correct email field and student number based on the selected category.
    // The form renders different email inputs for students and lecturers.
    if ($category === 'student') {
        $student_number = trim($_POST['student_number']);
        $email = trim($_POST['student_email']);
    } elseif ($category === 'lecturer') {
        $email = trim($_POST['lecturer_email']);
    }

    // Server-side validation — runs even if the user bypasses the JavaScript checks.
    if (empty($fullname) || empty($university) || empty($category) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!str_ends_with($email, '.lk')) {
        // Restrict registration to Sri Lankan university email addresses only.
        $error = "Email must end with .lk";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {

        // Check whether this email is already registered to prevent duplicate accounts.
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "This email is already registered! Please login.";
        } else {
            // Hash the password using bcrypt before storing — never store plain-text passwords.
            // Generate a random 6-digit OTP for email verification.
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $otp = rand(100000, 999999);

            try {
                // Insert the new user record along with the OTP into the database.
                // is_verified defaults to 0 until the user completes OTP verification.
                $insert_stmt = $pdo->prepare("INSERT INTO users (fullname, university, category, student_number, email, password, otp) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$fullname, $university, $category, $student_number, $email, $hashed_password, $otp]);

                // --- Send OTP verification email via PHPMailer + Gmail SMTP ---
                $mail = new PHPMailer(true); // Passing true enables exceptions on error.

                try {
                    // Configure PHPMailer to send via Gmail's SMTP server over SSL (port 465).
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'pramodya954@gmail.com'; // Gmail address used as the sender.
                    $mail->Password   = 'rqmuevyyahcagcgk';      // 16-character Gmail App Password (not the account password).
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    // Set the sender name shown in the recipient's inbox and the destination address.
                    $mail->setFrom('pramodya954@gmail.com', 'CampusHub Security');
                    $mail->addAddress($email, $fullname);

                    // Build the HTML email body with the OTP displayed prominently.
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your CampusHub Account (OTP)';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                            <h2 style='color: #F97316; text-align: center;'>CampusHub</h2>
                            <p>Hello <b>{$fullname}</b>,</p>
                            <p>Thank you for registering on CampusHub. To activate your university account, please use the following 6-digit OTP code:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <span style='font-size: 24px; font-weight: bold; background: #f3f4f6; padding: 15px 25px; border-radius: 8px; letter-spacing: 5px; color: #111;'>{$otp}</span>
                            </div>
                            <p>If you did not request this, please ignore this email.</p>
                            <p>Best Regards,<br>The CampusHub Team</p>
                        </div>
                    ";

                    $mail->send();

                    // Email sent successfully — redirect to the OTP verification page.
                    // urlencode() ensures the email address is safely embedded in the URL.
                    header("Location: verify.php?email=" . urlencode($email));
                    exit(); // Stop further PHP execution after the redirect.

                } catch (Exception $e) {
                    // Email delivery failed — inform the user but the account was still created.
                    // $mail->ErrorInfo contains the detailed SMTP error message from PHPMailer.
                    $error = "Account created, but OTP email could not be sent. Error: {$mail->ErrorInfo}";
                }

            } catch (PDOException $e) {
                // Database insert failed — show a generic error to avoid leaking SQL details.
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-main">
    <div class="auth-box" style="max-width: 500px;">
        <div class="auth-header">
            <h2>Create an Account</h2>
            <p>Join the CampusHub community today</p>
        </div>

        <!-- Show server-side error messages returned from the PHP validation block above. -->
        <?php if(!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Show server-side success messages (e.g. after a resend OTP action). -->
        <?php if(!empty($success)): ?>
            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: 600;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form" id="registerForm">

            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="fullname" placeholder="Full Name" required>
            </div>

            <!-- Dropdown lists all 17 recognised Sri Lankan state universities. -->
            <div class="input-group">
                <i class="fas fa-university input-icon"></i>
                <select name="university" required class="custom-select">
                    <option value="" disabled selected>Select Your University</option>
                    <option value="Colombo">University of Colombo (කොළඹ)</option>
                    <option value="Peradeniya">University of Peradeniya (පේරාදෙණිය)</option>
                    <option value="Sri Jayewardenepura">University of Sri Jayewardenepura (ශ්‍රී ජයවර්ධනපුර)</option>
                    <option value="Kelaniya">University of Kelaniya (කැලණිය)</option>
                    <option value="Moratuwa">University of Moratuwa (මොරටුව)</option>
                    <option value="Jaffna">University of Jaffna (යාපනය)</option>
                    <option value="Ruhuna">University of Ruhuna (රුහුණ)</option>
                    <option value="Open University">The Open University of Sri Lanka (විවෘත)</option>
                    <option value="Eastern">Eastern University, Sri Lanka (නැගෙනහිර)</option>
                    <option value="South Eastern">South Eastern University of Sri Lanka (ගිනිකොනදිග)</option>
                    <option value="Rajarata">Rajarata University of Sri Lanka (රජරට)</option>
                    <option value="Sabaragamuwa">Sabaragamuwa University of Sri Lanka (සබරගමුව)</option>
                    <option value="Wayamba">Wayamba University of Sri Lanka (වයඹ)</option>
                    <option value="Uva Wellassa">Uva Wellassa University (ඌව වෙල්ලස්ස)</option>
                    <option value="Visual & Performing Arts">University of the Visual & Performing Arts (සෞන්දර්ය කලා)</option>
                    <option value="Gampaha Wickramarachchi">Gampaha Wickramarachchi University (ගම්පහ වික්‍රමාරච්චි)</option>
                    <option value="Vavuniya">Vavuniya University (වවුනියාව)</option>
                </select>
            </div>

            <!-- Category selection controls which email/student-number fields are shown below. -->
            <div class="input-group">
                <i class="fas fa-users-cog input-icon"></i>
                <select name="category" id="userCategory" required class="custom-select">
                    <option value="" disabled selected>Select Category</option>
                    <option value="student">1. Student</option>
                    <option value="lecturer">2. Lecturer</option>
                </select>
            </div>

            <!-- Student-specific fields — hidden by default, revealed via JS when "Student" is selected. -->
            <div id="studentSection" class="dynamic-section" style="display: none;">
                <div class="input-group">
                    <i class="fas fa-id-card input-icon"></i>
                    <input type="text" name="student_number" placeholder="Student Number (e.g. PS/2022/142)">
                </div>
                <div class="input-group" style="margin-top: 20px;">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="student_email" class="lk-email" placeholder="Student Email (must end with .lk)">
                </div>
            </div>

            <!-- Lecturer-specific fields — hidden by default, revealed via JS when "Lecturer" is selected. -->
            <div id="lecturerSection" class="dynamic-section" style="display: none;">
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="lecturer_email" class="lk-email" placeholder="Lecturer Email (must end with .lk)">
                </div>
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="regPassword" placeholder="Create Password" required>
                <!-- Eye icon toggles password visibility — handled by setupPasswordToggle() below. -->
                <i class="fas fa-eye toggle-password" id="toggleRegPassword"></i>
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="confirm_password" id="regConfirmPassword" placeholder="Confirm Password" required>
                <i class="fas fa-eye toggle-password" id="toggleRegConfirmPassword"></i>
            </div>

            <!-- Client-side error messages are injected here by the JS submit handler. -->
            <div id="errorBox" style="color: #ef4444; font-size: 0.9rem; display: none; text-align: center; font-weight: 600;"></div>

            <button type="submit" class="btn-auth">Verify via Email (OTP)</button>
        </form>

        <p class="auth-footer">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const categorySelect   = document.getElementById('userCategory');
    const studentSection   = document.getElementById('studentSection');
    const lecturerSection  = document.getElementById('lecturerSection');

    // Show or hide the student/lecturer input sections based on the selected category.
    // Both sections are hidden first to reset state before revealing the correct one.
    categorySelect.addEventListener('change', function() {
        studentSection.style.display  = 'none';
        lecturerSection.style.display = 'none';

        if (this.value === 'student')  studentSection.style.display  = 'block';
        if (this.value === 'lecturer') lecturerSection.style.display = 'block';
    });

    // Reusable helper that wires a show/hide toggle button to its password input field.
    // Clicking the eye icon switches the input between type="password" and type="text".
    function setupPasswordToggle(toggleId, inputId) {
        const toggleBtn  = document.getElementById(toggleId);
        const inputField = document.getElementById(inputId);
        if (toggleBtn && inputField) {
            toggleBtn.addEventListener('click', function() {
                const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
                inputField.setAttribute('type', type);
                // Swap the icon between open-eye and slashed-eye to reflect current state.
                this.classList.toggle('fa-eye-slash');
            });
        }
    }
    setupPasswordToggle('toggleRegPassword', 'regPassword');
    setupPasswordToggle('toggleRegConfirmPassword', 'regConfirmPassword');

    const registerForm = document.getElementById('registerForm');
    const errorBox     = document.getElementById('errorBox');

    // Client-side validation runs on submit — catches obvious errors before hitting the server.
    // e.preventDefault() stops the form from submitting if validation fails.
    registerForm.addEventListener('submit', function(e) {
        errorBox.style.display = 'none';
        let isValid = true;
        let errorMessage = "";
        let emailToCheck = "";

        const activeCategory = categorySelect.value;

        // Pick the correct email input depending on the selected category.
        if (activeCategory === 'student') {
            emailToCheck = document.querySelector('input[name="student_email"]').value.trim();
        } else if (activeCategory === 'lecturer') {
            emailToCheck = document.querySelector('input[name="lecturer_email"]').value.trim();
        }

        // Enforce .lk domain restriction on the client side as well.
        if (emailToCheck === "" || !emailToCheck.endsWith('.lk')) {
            isValid = false;
            errorMessage = "Please enter a valid University official email ending with '.lk'";
        }

        // Confirm that both password fields match before allowing submission.
        const pwd  = document.getElementById('regPassword').value;
        const cpwd = document.getElementById('regConfirmPassword').value;
        if (pwd !== cpwd) {
            isValid = false;
            errorMessage = "Passwords do not match!";
        }

        if (!isValid) {
            e.preventDefault(); // Block form submission and show the error inline.
            errorBox.innerText = errorMessage;
            errorBox.style.display = 'block';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>