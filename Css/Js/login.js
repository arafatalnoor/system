// Function to check lecturer status from server
async function checkLecturerStatus() {
    try {
        const response = await fetch('check_lecturer.php');
        const data = await response.json();
        return data.lecturerExists;
    } catch (error) {
        console.error('Error checking lecturer status:', error);
        return false;
    }
}

// Function to update role options only in register form
async function updateRoleOptions() {
    const lecturerExists = await checkLecturerStatus();
    
    // Only update the register form's role select
    const registerRoleSelect = document.querySelector('#register-form select[name="role"]');
    if (registerRoleSelect) {
        const lecturerOption = registerRoleSelect.querySelector('option[value="lecturer"]');
        if (lecturerOption && lecturerExists) {
            lecturerOption.remove();
        }
    }
}

// Toggle between login and register forms
function toggleForm() {
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");

    if (loginForm.style.display === "none") {
        loginForm.style.display = "block";
        registerForm.style.display = "none";
    } else {
        loginForm.style.display = "none";
        registerForm.style.display = "block";
    }
}

// Handle login form submission
function handleLoginSubmit() {
    const roleSelect = document.getElementById('role');
    const errorMessage = document.getElementById('error-message');
    
    if (!roleSelect.value) {
        errorMessage.style.display = 'block';
        return false;
    }
    
    errorMessage.style.display = 'none';
    return true;
}

// Handle register form submission
async function handleRegisterSubmit() {
    const form = document.getElementById('registerForm');
    const roleSelect = form.querySelector('select[name="role"]');
    
    if (roleSelect.value === 'lecturer') {
        try {
            const formData = new FormData(form);
            const response = await fetch('register_lecturer.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Registration failed');
            }
            
            await updateRoleOptions();
        } catch (error) {
            console.error('Error registering lecturer:', error);
            return false;
        }
    }
    
    return true;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateRoleOptions();
});
