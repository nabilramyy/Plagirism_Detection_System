<?php
/**
 * Login/Signup Page View
 * Located at: app/Views/auth/login.php
 * Accessed via: /login or /signup
 */;
 
// This view should only be accessible through index.php
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/app/Helpers/SessionManager.php';
use Helpers\SessionManager;

$session = SessionManager::getInstance();

// Display auth errors if any
$authError = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);

// Get success messages
$signupSuccess = isset($_GET['signup']) && $_GET['signup'] === 'success';
$resetSuccess = isset($_GET['reset']) && $_GET['reset'] === 'success';
$logoutSuccess = isset($_GET['logout']) && $_GET['logout'] === 'success';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Login and Registration - Similyze</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Google Sign-In function
        async function signInWithGoogle(context, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            if (context === 'signup') {
                const roleRadios = document.querySelectorAll('#signup-role-selection input[name="role"]');
                let selectedRole = null;
                
                roleRadios.forEach(radio => {
                    if (radio.checked) {
                        selectedRole = radio.value;
                    }
                });
                
                if (!selectedRole) {
                    alert('Please select whether you are a Student or Instructor before continuing with Google signup.');
                    return false;
                }
                
                try {
                    await fetch('<?= BASE_URL ?>/app/Controllers/AuthController.php?action=set_google_role&role=' + selectedRole, {
                        method: 'GET',
                        credentials: 'same-origin'
                    });
                    window.location.href = '<?= BASE_URL ?>/app/Controllers/AuthController.php?action=google_auth';
                } catch (error) {
                    console.error('Failed to set role:', error);
                    alert('Failed to set role. Please try again.');
                    return false;
                }
            } else {
                window.location.href = '<?= BASE_URL ?>/app/Controllers/AuthController.php?action=google_auth';
            }
            
            return false;
        }

        document.addEventListener("keydown", function(e) {
            if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === "x") {
                e.preventDefault();
                const adminBox = document.getElementById("admin-key-box");
                
                if (adminBox.style.display === "block") {
                    adminBox.style.display = "none";
                    document.getElementById("admin_key").value = "";
                } else {
                    adminBox.style.display = "block";
                    document.getElementById("admin_key").focus();
                }
            }
        });

        function showForgotPassword(e) {
            e.preventDefault();
            clearAllForms();
            document.getElementById('flip-forgot').checked = true;
        }

        function backToLogin(e) {
            e.preventDefault();
            clearAllForms();
            document.getElementById('flip-forgot').checked = false;
        }

        function clearAllForms() {
            document.getElementById('login-email').value = '';
            document.getElementById('login-password').value = '';
            document.getElementById('admin_key').value = '';
            document.getElementById('admin-key-box').style.display = 'none';
            
            document.getElementById('signup-name').value = '';
            document.getElementById('signup-email').value = '';
            document.getElementById('signup-mobile').value = '';
            document.getElementById('signup-country').value = '';
            document.getElementById('signup-password').value = '';
            document.getElementById('confirm-password').value = '';
            
            const roleRadios = document.querySelectorAll('input[name="role"]');
            roleRadios.forEach(radio => radio.checked = false);
            
            document.getElementById('forgot-name').value = '';
            document.getElementById('forgot-email').value = '';
            document.getElementById('forgot-mobile').value = '';
            document.getElementById('forgot-password').value = '';
            document.getElementById('forgot-confirm-password').value = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const flipCheckbox = document.getElementById('flip');
            flipCheckbox.addEventListener('change', function() {
                clearAllForms();
            });

            const flipForgotCheckbox = document.getElementById('flip-forgot');
            flipForgotCheckbox.addEventListener('change', function() {
                clearAllForms();
            });
        });
    </script>
</head>
<body>
    <?php if ($authError): ?>
        <script>
            alert('<?= htmlspecialchars($authError, ENT_QUOTES) ?>');
        </script>
    <?php endif; ?>

    <?php if ($signupSuccess): ?>
        <script>
            alert('Signup successful! You can now log in.');
            window.onload = function() {
                document.getElementById('flip').checked = false;
                clearAllForms();
            };
        </script>
    <?php endif; ?>

    <?php if ($resetSuccess): ?>
        <script>
            alert('Password reset successful! You can now log in with your new password.');
            window.onload = function() {
                document.getElementById('flip').checked = false;
                document.getElementById('flip-forgot').checked = false;
                clearAllForms();
            };
        </script>
    <?php endif; ?>

    <?php if ($logoutSuccess): ?>
        <script>
            alert('You have been logged out successfully.');
            clearAllForms();
        </script>
    <?php endif; ?>
    
    <!-- 3D Floating Robot Icons Background -->
    <div class="floating-shape shape-1">
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <rect x="30" y="25" width="40" height="50" rx="5" fill="rgba(59, 130, 246, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
            <circle cx="42" cy="40" r="4" fill="rgba(30, 58, 138, 0.6)"/>
            <circle cx="58" cy="40" r="4" fill="rgba(30, 58, 138, 0.6)"/>
            <rect x="42" y="50" width="16" height="8" rx="2" fill="rgba(30, 58, 138, 0.5)"/>
        </svg>
    </div>
    
    <div class="container">
        <input type="checkbox" id="flip">
        <input type="checkbox" id="flip-forgot">
        
        <div class="forms-wrapper">
            <div class="forms">
                <!-- LOGIN FORM -->
                <div class="form-content login-form">
                    <div class="title">Login</div>
                    <p class="subtitle">Welcome back! Please login to your account</p>
                    <form action="<?= BASE_URL ?>/app/Controllers/AuthController.php?action=login" method="post">
                        <div id="admin-key-box" class="input-box admin-key" style="display:none;">
                            <i class="fas fa-key"></i>
                            <input type="text" id="admin_key" name="admin_key" placeholder="Enter Admin Secret Key">
                        </div>

                        <div class="input-box">
                            <label>Email Address</label>
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="login-email" name="email" placeholder="Enter your email" required>
                        </div>
                        
                        <div class="input-box">
                            <label>Password</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                        </div>
                        
                        <div class="form-options">
                            <label>
                                <input type="checkbox"> Remember me
                            </label>
                            <a href="#" onclick="showForgotPassword(event)">Forgot password?</a>
                        </div>
                        
                        <div class="button">
                            <input type="submit" value="LOGIN">
                        </div>
                        
                        <div class="divider">
                            <span>OR</span>
                        </div>
                        
                        <div class="google-signin-wrapper">
                            <button type="button" class="google-signin-btn" onclick="signInWithGoogle('login', event); return false;">
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <g fill="#000" fill-rule="evenodd">
                                        <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                                        <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.91l2.78 2.15c1.9-1.75 2.69-4.32 2.69-7.56z" fill="#4285F4"/>
                                        <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.78-2.15c-.76.53-1.78.9-3.18.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                                    </g>
                                </svg>
                                Continue with Google
                            </button>
                        </div>
                        
                        <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label></div>
                    </form>
                </div>

                <!-- SIGNUP FORM -->
                <div class="form-content signup-form">
                    <div class="title">Create Account</div>
                    <p class="subtitle">Join us to protect academic integrity</p>
                    <form action="<?= BASE_URL ?>/app/Controllers/AuthController.php?action=signup" method="post">
                        <div class="input-box">
                            <label>Full Name</label>
                            <i class="fas fa-user"></i>
                            <input type="text" id="signup-name" name="name" placeholder="Enter your name" required minlength="3">
                        </div>
                        
                        <div class="input-box">
                            <label>Email Address</label>
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="signup-email" name="email" placeholder="Enter your email" required>
                        </div>
                        
                        <div class="input-box">
                            <label>Mobile Number</label>
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="signup-mobile" name="mobile" placeholder="Enter your mobile number" required pattern="\d{11}" title="Please enter a 11-digit mobile number">
                        </div>
                        
                        <div class="input-box select-box">
                            <label>Country</label>
                            <i class="fas fa-globe"></i>
                            <select id="signup-country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="us">United States</option>
                                <option value="ca">Canada</option>
                                <option value="in">India</option>
                                <option value="uk">United Kingdom</option>
                                <option value="eg">Egypt</option>
                                <option value="au">Australia</option>
                                <option value="fr">France</option>
                                <option value="de">Germany</option>
                                <option value="br">Brazil</option>
                                <option value="za">South Africa</option>
                            </select>
                        </div>
                        
                        <div class="input-box">
                            <label>Role</label>
                            <div class="role-selection" id="signup-role-selection">
                                <div class="role-option">
                                    <input type="radio" id="signup-role-student" name="role" value="student" required>
                                    <label for="signup-role-student">Student</label>
                                </div>
                                <div class="role-option">
                                    <input type="radio" id="signup-role-instructor" name="role" value="instructor" required>
                                    <label for="signup-role-instructor">Instructor</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-box">
                            <label>Password</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="signup-password" name="password" placeholder="Enter your password" required pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$" title="Must have 8+ chars, uppercase, number, special char">
                        </div>
                        
                        <div class="input-box">
                            <label>Confirm Password</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                        </div>
                        
                        <div class="button">
                            <input type="submit" value="SIGNUP">
                        </div>
                        
                        <div class="divider">
                            <span>OR</span>
                        </div>
                        
                        <div class="google-signin-wrapper">
                            <button type="button" class="google-signin-btn" onclick="signInWithGoogle('signup', event); return false;">
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <g fill="#000" fill-rule="evenodd">
                                        <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                                        <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.91l2.78 2.15c1.9-1.75 2.69-4.32 2.69-7.56z" fill="#4285F4"/>
                                        <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.78-2.15c-.76.53-1.78.9-3.18.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                                    </g>
                                </svg>
                                Continue with Google
                            </button>
                        </div>
                        
                        <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label></div>
                    </form>
                </div>

                <!-- FORGOT PASSWORD FORM -->
                <div class="form-content forgot-form">
                    <div class="title">Reset Password</div>
                    <p class="subtitle">Enter your details to reset your password</p>
                    <form action="<?= BASE_URL ?>/app/Controllers/AuthController.php?action=forgot_password" method="post">
                        <div class="input-box">
                            <label>Full Name</label>
                            <i class="fas fa-user"></i>
                            <input type="text" id="forgot-name" name="name" placeholder="Enter your name" required>
                        </div>
                        
                        <div class="input-box">
                            <label>Email Address</label>
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required>
                        </div>
                        
                        <div class="input-box">
                            <label>Mobile Number</label>
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="forgot-mobile" name="mobile" placeholder="Enter your mobile number" required pattern="\d{11}">
                        </div>
                        
                        <div class="input-box">
                            <label>New Password</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="forgot-password" name="password" placeholder="Enter new password" required>
                        </div>
                        
                        <div class="input-box">
                            <label>Confirm New Password</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="forgot-confirm-password" name="confirm-password" placeholder="Confirm new password" required>
                        </div>
                        
                        <div class="button">
                            <input type="submit" value="RESET PASSWORD">
                        </div>
                        
                        <div class="text sign-up-text"><a href="#" onclick="backToLogin(event)">Back to Login</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>