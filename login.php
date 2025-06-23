<?php
// Start the session
session_start();

// Include database connection file
include("connection.php");

// Initialize the error variable
$error = "";
$loginSuccess = false;

// Check if form is submitted
if (isset($_POST["login"])) {
    // Define email and password variables and prevent SQL injection
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password']; // No need to escape this as it's not directly used in the query

    // Fetch user from database based on email
    $sql = "SELECT id, email, role, password, active, names,campus image FROM users WHERE email='$email'";
    $result = mysqli_query($connection, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // Verify hashed password retrieved from the database
        if (password_verify($password, $row['password'])) {
            if ($row['active'] == '1') {
                // Password is correct, start a new session
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['id'] = $row['id'];
                $_SESSION['campus'] = $row['campus'];
                $loginSuccess = true;
            } else {
                $error = "Sorry! Your account is deactivated by the admin.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email not found.";
    }

    // AJAX response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        if ($loginSuccess) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $error]);
        }
        exit;
    }

    // Normal POST (non-AJAX)
    if ($loginSuccess) {
        echo "<script>window.location.href='Dashboard/index.php'</script>";
        exit; // Exit script after redirection
    }
}

// Prepare PHP error for JavaScript
$phpError = !empty($error) ? json_encode($error) : 'null';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Rwanda - Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Skeleton Loading Screen */
        .skeleton-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease-out;
        }

        .skeleton-loader.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .skeleton-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }

        .skeleton-logo i {
            font-size: 50px;
            color: white;
        }

        .skeleton-text {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .skeleton-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .skeleton-text p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .skeleton-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .skeleton-progress {
            width: 300px;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            margin-top: 30px;
            overflow: hidden;
        }

        .skeleton-progress-bar {
            height: 100%;
            background: white;
            border-radius: 3px;
            width: 0%;
            animation: progress 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        /* Background iframe */
        .background-iframe {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.1;
            filter: blur(2px);
            transition: opacity 1s ease-in-out;
        }

        .background-iframe.loaded {
            opacity: 0.1;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.6s ease-out;
        }

        .header.show {
            opacity: 1;
            transform: translateY(0);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .logo-text h4 {
            color: #1e3c72;
            margin: 0;
            font-weight: 600;
        }

        .logo-text p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }

        .header-links {
            display: flex;
            gap: 20px;
        }

        .header-link {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .header-link:hover {
            color: #2a5298;
            text-decoration: none;
        }

        /* Main Container */
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 20px;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease-out;
        }

        .main-container.show {
            opacity: 1;
            transform: translateY(0);
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3c72, #2a5298, #667eea);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #1e3c72;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 16px;
        }

        .ur-logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            background: white;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 45px;
            color: #999;
        }

        .input-group-text {
            background: rgba(30, 60, 114, 0.1);
            border: 2px solid #e1e5e9;
            border-right: none;
            color: #1e3c72;
            font-weight: 600;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 60, 114, 0.3);
        }

        .btn-info {
            background: linear-gradient(45deg, #17a2b8, #20c997);
            color: white;
            width: 100%;
            margin-bottom: 20px;
        }

        .btn-info:hover {
            background: linear-gradient(45deg, #20c997, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.3);
        }

        .link-btn {
            background: none;
            border: none;
            color: #1e3c72;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .link-btn:hover {
            color: #2a5298;
        }

        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 4px solid #28a745;
        }

        /* Demo Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .demo-account {
            padding: 15px;
            border-radius: 10px;
            background: rgba(30, 60, 114, 0.05);
            border-left: 4px solid #1e3c72;
        }

        .demo-account h6 {
            color: #1e3c72;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .demo-account p {
            margin-bottom: 5px;
            color: #666;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .header-links {
                gap: 15px;
            }

            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }

            .main-container {
                padding: 120px 10px 20px;
            }

            .skeleton-text h2 {
                font-size: 2rem;
            }

            .skeleton-text p {
                font-size: 1rem;
            }

            .skeleton-progress {
                width: 250px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Button Loading States */
        .btn.loading {
            position: relative;
            color: transparent !important;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .btn.loading .btn-text {
            opacity: 0;
        }

        .btn.loading .btn-icon {
            opacity: 0;
        }
    </style>
</head>
<body>
    <!-- Skeleton Loading Screen -->
    <div class="skeleton-loader" id="skeleton-loader">
        <div class="skeleton-logo">
            <i class="fas fa-university"></i>
        </div>
        <div class="skeleton-text">
            <h2>University of Rwanda</h2>
            <p>Hostel Management System</p>
        </div>
        <div class="skeleton-spinner"></div>
        <div class="skeleton-progress">
            <div class="skeleton-progress-bar"></div>
        </div>
    </div>

    <!-- Background iframe -->
    <iframe src="https://ur.ac.rw/" class="background-iframe" frameborder="0" id="background-iframe"></iframe>
    <div class="overlay"></div>

    <!-- Header -->
    <header class="header" id="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-university"></i>
                </div>
                <div class="logo-text">
                    <h4>University of Rwanda</h4>
                    <p>Hostel Management System</p>
                </div>
            </div>
            <div class="header-links">
                <a href="https://ur.ac.rw/" class="header-link" target="_blank">
                    <i class="fas fa-globe"></i> Official Website
                </a>
                <a href="#" class="header-link">
                    <i class="fas fa-question-circle"></i> Help
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container" id="main-container">
        <div class="login-container">
            <div class="login-header">
                <img src="./assets/img/ur.png" alt="University of Rwanda" class="ur-logo">
                <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
                <p>Access your hostel management account</p>
            </div>

            <!-- Display error message if any -->
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="post" action="login.php" id="loginForm">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#demoModal">
                    <i class="fas fa-users btn-icon"></i> 
                    <span class="btn-text">Demo Accounts</span>
                </button>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">@</span>
                        </div>
                        <input type="email" name="email" class="form-control" id="email" 
                               placeholder="Enter your email address" required autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" name="password" class="form-control" id="password" 
                           placeholder="Enter your password" required>
                    <i class="fas fa-key input-icon"></i>
                </div>

                <div class="form-group">
                    <a href="reset.php" class="link-btn">
                        <i class="fas fa-key"></i> Reset Password
                    </a>
                </div>

                <button type="submit" class="btn btn-primary" name="login" id="login-btn">
                    <i class="fas fa-sign-in-alt btn-icon"></i> 
                    <span class="btn-text">Login</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Demo Accounts Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="demoModalLabel">
                        <i class="fas fa-users"></i> Demo Accounts
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="demo-accounts">
                        <div class="demo-account mb-3">
                            <h6><i class="fas fa-user-shield"></i> Admin Account</h6>
                            <p><strong>Email:</strong> cedrickhakuzimana@gmail.com</p>
                            <p><strong>Password:</strong> 1234</p>
                            <button class="btn btn-sm btn-primary use-demo" 
                                    data-email="cedrickhakuzimana@gmail.com" data-pass="1234">
                                <i class="fas fa-user"></i> Use This Account
                            </button>
                        </div>
                        <hr>
                        <div class="demo-account mb-3">
                            <h6><i class="fas fa-user"></i> Huye Welfare Account</h6>
                            <p><strong>Email:</strong> akimana@gmail.com</p>
                            <p><strong>Password:</strong> 1234</p>
                            <button class="btn btn-sm btn-primary use-demo" 
                                    data-email="akimana@gmail.com" data-pass="1234">
                                <i class="fas fa-user"></i> Use This Account
                            </button>
                        </div>
                        <hr>
                        <div class="demo-account mb-3">
                            <h6><i class="fas fa-user"></i> Remera Welfare Account</h6>
                            <p><strong>Email:</strong> akimana@gmail.com</p>
                            <p><strong>Password:</strong> 1234</p>
                            <button class="btn btn-sm btn-primary use-demo" 
                                    data-email="akimana@gmail.com" data-pass="1234">
                                <i class="fas fa-user"></i> Use This Account
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error caught:', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error,
        stack: e.error ? e.error.stack : 'No stack trace'
    });
});

// Unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', {
        reason: e.reason,
        promise: e.promise
    });
});

// PHP error variable (safely prepared)
var phpError = <?php echo $phpError; ?>;

// Skeleton Loading Handler
$(document).ready(function() {
    console.log('Login page initialized');
    
    // Log PHP error if exists
    if (phpError !== null) {
        console.error('PHP Error:', phpError);
    }
    
    try {
        const iframe = document.getElementById('background-iframe');
        const skeletonLoader = document.getElementById('skeleton-loader');
        const header = document.getElementById('header');
        const mainContainer = document.getElementById('main-container');
        
        if (!iframe) {
            console.error('Background iframe not found');
            return;
        }
        
        if (!skeletonLoader) {
            console.error('Skeleton loader not found');
            return;
        }
        
        if (!header) {
            console.error('Header not found');
            return;
        }
        
        if (!mainContainer) {
            console.error('Main container not found');
            return;
        }
        
        console.log('All DOM elements found successfully');
        
        // Function to hide skeleton and show content
        function showContent() {
            try {
                console.log('Showing content');
                
                // Add loaded class to iframe
                iframe.classList.add('loaded');
                
                // Fade out skeleton loader
                skeletonLoader.classList.add('fade-out');
                
                // After skeleton fades out, show main content
                setTimeout(() => {
                    try {
                        skeletonLoader.style.display = 'none';
                        
                        // Show header and main container with animations
                        header.classList.add('show');
                        mainContainer.classList.add('show');
                        
                        console.log('Content animation completed');
                    } catch (error) {
                        console.error('Error in content animation:', error);
                    }
                }, 500);
            } catch (error) {
                console.error('Error in showContent function:', error);
            }
        }
        
        // Check if iframe loads successfully
        iframe.onload = function() {
            try {
                console.log('Background iframe loaded successfully');
                // Add a small delay to ensure smooth transition
                setTimeout(showContent, 1000);
            } catch (error) {
                console.error('Error in iframe onload:', error);
                showContent(); // Fallback
            }
        };
        
        // Fallback: if iframe fails to load or takes too long, show content anyway
        setTimeout(function() {
            try {
                if (!iframe.classList.contains('loaded')) {
                    console.log('Iframe load timeout - showing content anyway');
                    showContent();
                }
            } catch (error) {
                console.error('Error in iframe timeout handler:', error);
                showContent(); // Fallback
            }
        }, 5000); // 5 second fallback
        
        // Also handle iframe load errors
        iframe.onerror = function() {
            console.error('Iframe failed to load');
            showContent();
        };
        
    } catch (error) {
        console.error('Error in skeleton loading initialization:', error);
    }

    // Handle demo account selection
    try {
        $('.use-demo').click(function() {
            try {
                const email = $(this).data('email');
                const password = $(this).data('pass');
                
                console.log('Demo account selected:', { email: email, password: password });
                
                $('#email').val(email);
                $('#password').val(password);
                
                // Close the modal
                $('#demoModal').modal('hide');
                
                console.log('Demo account applied successfully');
            } catch (error) {
                console.error('Error applying demo account:', error);
            }
        });
    } catch (error) {
        console.error('Error setting up demo account handlers:', error);
    }

    // Handle form submission with loading state and AJAX
    try {
        $('#loginForm').submit(function(e) {
            e.preventDefault(); // Prevent default form submission
            try {
                console.log('Login form submitted');
                const $btn = $('#login-btn');
                const email = $('#email').val();
                const password = $('#password').val();
                console.log('Form data:', { email: email, password: password ? '***' : 'empty' });
                $btn.addClass('loading').prop('disabled', true);
                // Submit form via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'login.php',
                    data: {
                        login: 'submit',
                        email: email,
                        password: password
                    },
                    dataType: 'json',
                    success: function(response) {
                        $btn.removeClass('loading').prop('disabled', false);
                        console.log('Login response:', response);
                        if (response.status === 'success') {
                            window.location.href = 'Dashboard/index.php';
                        } else {
                            console.error('Login failed:', response.message);
                            alert(response.message || 'Login failed. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.removeClass('loading').prop('disabled', false);
                        console.error('AJAX error:', { xhr: xhr, status: status, error: error });
                        alert('Login failed. Please try again.');
                    }
                });
            } catch (error) {
                console.error('Error in form submission handler:', error);
                $btn.removeClass('loading').prop('disabled', false);
                alert('An error occurred. Please try again.');
            }
        });
    } catch (error) {
        console.error('Error setting up form submission handler:', error);
    }
    
    // Additional error logging for common issues
    try {
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap not detected - modal functionality may not work');
        } else {
            console.log('Bootstrap detected successfully');
        }
        
        // Check if jQuery is loaded
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded - page functionality will be limited');
        } else {
            console.log('jQuery detected successfully');
        }
        
        // Check for required assets
        const urLogo = document.querySelector('.ur-logo');
        if (urLogo && !urLogo.complete) {
            console.warn('UR logo image may not be loading properly');
        }
        
        // Log page load completion
        console.log('Login page setup completed successfully');
        
    } catch (error) {
        console.error('Error in additional checks:', error);
    }
});

// Log any console errors that might occur during page load
console.log('Login page script loaded at:', new Date().toISOString());
</script>
</body>
</html>