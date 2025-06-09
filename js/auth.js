// Authentication JavaScript for SocialConnect

document.addEventListener('DOMContentLoaded', function() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Clear error messages on input
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearError(this.name);
        });
    });
});

async function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        username: formData.get('username'),
        password: formData.get('password')
    };
    
    // Clear previous errors
    clearAllErrors();
    
    // Validate inputs
    if (!data.username.trim()) {
        showError('username', 'Username or email is required');
        return;
    }
    
    if (!data.password) {
        showError('password', 'Password is required');
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Network error. Please try again.', 'error');
        console.error('Login error:', error);
    } finally {
        showLoading(false);
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        fullName: formData.get('fullName'),
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        confirmPassword: formData.get('confirmPassword')
    };
    
    // Clear previous errors
    clearAllErrors();
    
    // Validate inputs
    if (!validateRegistrationData(data)) {
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch('api/auth.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Registration successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Network error. Please try again.', 'error');
        console.error('Registration error:', error);
    } finally {
        showLoading(false);
    }
}

function validateRegistrationData(data) {
    let isValid = true;
    
    // Full name validation
    if (!data.fullName.trim()) {
        showError('fullName', 'Full name is required');
        isValid = false;
    } else if (data.fullName.trim().length < 2) {
        showError('fullName', 'Full name must be at least 2 characters');
        isValid = false;
    }
    
    // Username validation
    if (!data.username.trim()) {
        showError('username', 'Username is required');
        isValid = false;
    } else if (data.username.length < 3) {
        showError('username', 'Username must be at least 3 characters');
        isValid = false;
    } else if (data.username.length > 20) {
        showError('username', 'Username cannot exceed 20 characters');
        isValid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(data.username)) {
        showError('username', 'Username can only contain letters, numbers, and underscores');
        isValid = false;
    }
    
    // Email validation
    if (!data.email.trim()) {
        showError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(data.email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Password validation
    if (!data.password) {
        showError('password', 'Password is required');
        isValid = false;
    } else if (data.password.length < 6) {
        showError('password', 'Password must be at least 6 characters');
        isValid = false;
    }
    
    // Confirm password validation
    if (!data.confirmPassword) {
        showError('confirmPassword', 'Please confirm your password');
        isValid = false;
    } else if (data.password !== data.confirmPassword) {
        showError('confirmPassword', 'Passwords do not match');
        isValid = false;
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showError(fieldName, message) {
    const errorElement = document.getElementById(fieldName + 'Error');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    const inputElement = document.getElementById(fieldName);
    if (inputElement) {
        inputElement.style.borderColor = '#e74c3c';
    }
}

function clearError(fieldName) {
    const errorElement = document.getElementById(fieldName + 'Error');
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    const inputElement = document.getElementById(fieldName);
    if (inputElement) {
        inputElement.style.borderColor = '#eee';
    }
}

function clearAllErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
    
    const inputElements = document.querySelectorAll('input');
    inputElements.forEach(element => {
        element.style.borderColor = '#eee';
    });
}

function showMessage(message, type = 'info') {
    let messageContainer = document.getElementById('loginMessage') || document.getElementById('registerMessage');
    
    if (!messageContainer) {
        // Create message container if it doesn't exist
        messageContainer = document.createElement('div');
        messageContainer.className = 'message';
        const form = document.querySelector('.auth-form');
        form.parentNode.insertBefore(messageContainer, form.nextSibling);
    }
    
    messageContainer.textContent = message;
    messageContainer.className = `message ${type}`;
    messageContainer.style.display = 'block';
    
    // Auto-hide success messages
    if (type === 'success') {
        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 3000);
    }
}

function showLoading(show) {
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    
    submitButtons.forEach(button => {
        if (show) {
            button.disabled = true;
            button.style.opacity = '0.7';
            const originalText = button.innerHTML;
            button.dataset.originalText = originalText;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        } else {
            button.disabled = false;
            button.style.opacity = '1';
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    });
}

// Password strength indicator (optional enhancement)
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return strength;
}

// Show/hide password functionality
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Auto-focus first input on page load
window.addEventListener('load', function() {
    const firstInput = document.querySelector('input[type="text"], input[type="email"]');
    if (firstInput) {
        firstInput.focus();
    }
});
