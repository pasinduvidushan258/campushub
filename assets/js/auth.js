document.addEventListener("DOMContentLoaded", function() {

    // =================================================================
    // 1. Reusable Password Toggle Function (Show/Hide Password)
    // =================================================================
    /**
     * Wires an eye icon to toggle the visibility of a corresponding password input.
     * Reusable across login, registration, and password confirmation fields.
     * @param {string} toggleId - The DOM ID of the clickable icon (e.g., 'togglePassword')
     * @param {string} inputId  - The DOM ID of the password input field (e.g., 'password')
     */
    function setupPasswordToggle(toggleId, inputId) {
        const toggleBtn  = document.getElementById(toggleId);
        const inputField = document.getElementById(inputId);
        
        if (toggleBtn && inputField) {
            toggleBtn.addEventListener('click', function() {
                // Switch between type="password" (hidden) and type="text" (visible)
                const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
                inputField.setAttribute('type', type);
                
                // Swap the FontAwesome icon classes to reflect the current state
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    }

    // Apply the toggle functionality to the Login page
    setupPasswordToggle('togglePassword', 'password');
    
    // Apply the toggle functionality to the Register page
    setupPasswordToggle('toggleRegPassword', 'regPassword');
    setupPasswordToggle('toggleRegConfirmPassword', 'regConfirmPassword');


    // =================================================================
    // 2. Category Selection Logic (For Register Page)
    // =================================================================
    const categorySelect   = document.getElementById('userCategory');
    const studentSection   = document.getElementById('studentSection');
    const lecturerSection  = document.getElementById('lecturerSection');

    // Only execute this logic if the category dropdown exists on the current page
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            // Initially hide both sections to reset the UI state
            if (studentSection) studentSection.style.display  = 'none';
            if (lecturerSection) lecturerSection.style.display = 'none';

            // Reveal only the section that matches the user's selected category
            if (this.value === 'student' && studentSection)  studentSection.style.display  = 'block';
            if (this.value === 'lecturer' && lecturerSection) lecturerSection.style.display = 'block';
        });
    }


    // =================================================================
    // 3. Form Validation Logic (For Register Page)
    // =================================================================
    const registerForm = document.getElementById('registerForm');
    const errorBox     = document.getElementById('errorBox');

    // Only execute client-side validation if the registration form is present
    if (registerForm && errorBox) {
        registerForm.addEventListener('submit', function(e) {
            // Reset the error display on every new submission attempt
            errorBox.style.display = 'none';
            let isValid = true;
            let errorMessage = "";
            let emailToCheck = "";

            const activeCategory = categorySelect ? categorySelect.value : "";

            // Determine which email input to validate based on the selected category
            if (activeCategory === 'student') {
                const studentEmailInput = document.querySelector('input[name="student_email"]');
                if (studentEmailInput) emailToCheck = studentEmailInput.value.trim();
            } else if (activeCategory === 'lecturer') {
                const lecturerEmailInput = document.querySelector('input[name="lecturer_email"]');
                if (lecturerEmailInput) emailToCheck = lecturerEmailInput.value.trim();
            }

            // Client-side domain restriction: ensure the email ends with the official '.lk' suffix
            if (emailToCheck === "" || !emailToCheck.endsWith('.lk')) {
                isValid = false;
                errorMessage = "Please enter a valid University official email ending with '.lk'";
            }

            // Confirm that both password fields match exactly
            const pwd  = document.getElementById('regPassword');
            const cpwd = document.getElementById('regConfirmPassword');
            
            if (pwd && cpwd && pwd.value !== cpwd.value) {
                isValid = false;
                errorMessage = "Passwords do not match!";
            }

            // If validation fails, block form submission and display the error message
            if (!isValid) {
                e.preventDefault(); 
                errorBox.innerText = errorMessage;
                errorBox.style.display = 'block';
            }
        });
    }

    // =================================================================
    // 4. OTP Input Logic (For verify.php)
    // =================================================================
    const otpInputs = document.querySelectorAll('.otp-input');
    
    // Only execute this logic if OTP inputs exist on the current page
    if (otpInputs.length > 0) {
        
        // Immediately focus the first box so the user can start typing
        otpInputs[0].focus();

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // Remove any character that is not a digit (0-9)
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Move focus to the next input automatically if a digit was entered
                if (e.target.value !== '' && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                // Move focus to the previous box on Backspace if current box is empty
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    }

});