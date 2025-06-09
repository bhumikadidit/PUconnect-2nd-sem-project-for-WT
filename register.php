<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniConnect - Join Campus</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-graduation-cap"></i> UniConnect</h1>
                <p>Join your campus community today</p>
            </div>
            
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" required>
                    <span class="error-message" id="fullNameError"></span>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                    <span class="error-message" id="usernameError"></span>
                </div>
                
                <div class="form-group">
                    <label for="email">University Email</label>
                    <input type="email" id="email" name="email" placeholder="student@university.edu" required>
                    <span class="error-message" id="emailError"></span>
                </div>
                
                <div class="form-group">
                    <label for="university">University</label>
                    <input type="text" id="university" name="university" placeholder="e.g., Stanford University" required>
                    <span class="error-message" id="universityError"></span>
                </div>
                
                <div class="form-group">
                    <label for="major">Major/Field of Study</label>
                    <input type="text" id="major" name="major" placeholder="e.g., Computer Science" required>
                    <span class="error-message" id="majorError"></span>
                </div>
                
                <div class="form-group">
                    <label for="year">Academic Year</label>
                    <select id="year" name="year" required>
                        <option value="">Select your year</option>
                        <option value="Freshman">Freshman</option>
                        <option value="Sophomore">Sophomore</option>
                        <option value="Junior">Junior</option>
                        <option value="Senior">Senior</option>
                        <option value="Graduate">Graduate Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Staff">Staff</option>
                    </select>
                    <span class="error-message" id="yearError"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="error-message" id="passwordError"></span>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <span class="error-message" id="confirmPasswordError"></span>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            
            <div id="registerMessage" class="message"></div>
        </div>
    </div>
    
    <script src="js/auth.js"></script>
</body>
</html>
