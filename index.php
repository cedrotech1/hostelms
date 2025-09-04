<?php

include("connection.php");

// Query to select system status
$query = "SELECT * FROM system";
$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);
$status = $row['status'] ?? null;
$allow_message = $row['allow_message'] ?? null;

if ($status != "live") {
    header("Location: status.php");
    exit(); // Stop further script execution
}

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
            background: linear-gradient(135deg,rgb(32, 41, 87) 0%,rgb(33, 32, 110) 100%);
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
            background: linear-gradient(135deg,rgb(32, 41, 87) 0%,rgb(33, 32, 110) 100%);
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

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            width: 100%;
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
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
            display: none;
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

        /* Step Sections */
        .step-section {
            margin-bottom: 25px;
            padding: 25px;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            transition: all 0.3s ease;
        }

        .step-section.active {
            display: block;
            border-color: #1e3c72;
            background: white;
        }

        .step-section.completed {
            opacity: 0.6;
            pointer-events: none;
            display: block;
        }

        .step-section h5 {
            color: #1e3c72;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Spinner */
        .spinner-container {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #1e3c72;
        }

        /* Radio buttons */
        .form-check {
            margin-bottom: 15px;
        }

        .form-check-input {
            margin-right: 10px;
        }

        .form-check-label {
            font-weight: 500;
            color: #333;
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

        /* Footer */
        .footer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            text-align: center;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }

        .footer.show {
            opacity: 1;
            transform: translateY(0);
        }

        .footer p {
            color: #666;
            margin: 0;
            font-size: 14px;
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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                <a href="login.php" class="header-link">
                    <i class="fas fa-question-circle"></i> Staff Login
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container" id="main-container">
        <div class="login-container">
            

            <!-- Login Section -->
            <div class="login-section" id="login-section">

            <div class="login-header">
                <h2><i class="fas fa-sign-in-alt"></i> Student Login</h2>
            </div>
                <div class="alert alert-danger" id="login-error"></div>
                <div class="alert alert-success" id="login-success"></div>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="login_regnumber">
                            <i class="fas fa-id-card"></i> Registration Number
                        </label>
                        <input type="text" class="form-control" id="login_regnumber" name="login_regnumber" 
                               placeholder="Enter your registration number" required autocomplete="off">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="login_password" name="login_password" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-key input-icon"></i>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="login-btn">
                        <i class="fas fa-sign-in-alt btn-icon"></i> 
                        <span class="btn-text">Login</span>
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <button class="link-btn" id="show-forgot">
                        <i class="fas fa-key"></i> Create/Reset Password?
                    </button>
                </div>
            </div>

            <!-- Forgot Password Section -->
            <div id="forgot-section" style="display:none;">
                <div class="login-header">
                    <h2><i class="fas fa-key"></i> Reset Password</h2>
                </div>
                
                <div class="alert alert-danger" id="reset-error"></div>
                <div class="alert alert-success" id="reset-success"></div>
                
                <div class="spinner-container">
                    <div class="spinner-border" id="reset-spinner" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>

                <!-- Step 1: Regnumber -->
                <div class="step-section active" id="step-regnumber">
                    <h5><i class="fas fa-id-card"></i> Step 1: Registration Number</h5>
                    <form id="form-regnumber">
                        <div class="form-group">
                            <label for="reset_regnumber">Registration Number</label>
                            <input type="text" class="form-control" id="reset_regnumber" name="reset_regnumber" 
                                   placeholder="Enter your registration number" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Next
                        </button>
                    </form>
                </div>

            <!-- Step 2: NID -->
            <div class="step-section" id="step-nid">
                    <h5><i class="fas fa-id-badge"></i> Step 2: National ID</h5>
                <form id="form-nid">
                    <div class="form-group">
                        <label for="reset_nid">National ID</label>
                            <input type="text" class="form-control" id="reset_nid" name="reset_nid" 
                                   placeholder="Enter your national ID" required autocomplete="off">
                    </div>
                        <button type="submit" class="btn btn-primary" id="nid-btn">
                            <i class="fas fa-arrow-right btn-icon"></i> <span class="btn-text">Next</span>
                        </button>
                </form>
            </div>

            <!-- Step 3: Auth Method -->
            <div class="step-section" id="step-auth-method">
                    <h5><i class="fas fa-mobile-alt"></i> Step 3: Choose Method</h5>
                <form id="form-auth-method">
                    <div class="form-group">
                            <label>Choose authentication method:</label>
                            <div class="form-check">
                            <input class="form-check-input" type="radio" name="auth_method" id="auth_email" value="email" required>
                                <label class="form-check-label" for="auth_email">
                                    <i class="fas fa-envelope"></i> Email (<span id="show-email"></span>)
                                </label>
                        </div>
                            <div class="form-check">
                            <input class="form-check-input" type="radio" name="auth_method" id="auth_sms" value="sms" required>
                                <label class="form-check-label" for="auth_sms">
                                    <i class="fas fa-sms"></i> SMS (<span id="show-phone"></span>)
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="auth-method-btn">
                            <i class="fas fa-paper-plane btn-icon"></i> <span class="btn-text">Send Code</span>
                        </button>
                </form>
            </div>

            <!-- Step 4: Enter Code -->
            <div class="step-section" id="step-code">
                    <h5><i class="fas fa-shield-alt"></i> Step 4: Verification Code</h5>
                <form id="form-code">
                    <div class="form-group">
                        <label for="reset_code">Enter the code you received</label>
                            <input type="text" class="form-control" id="reset_code" name="reset_code" 
                                   placeholder="Enter verification code" required autocomplete="off">
                    </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Verify Code
                        </button>
                </form>
            </div>

            <!-- Step 5: New Password -->
            <div class="step-section" id="step-password">
                    <h5><i class="fas fa-lock"></i> Step 5: New Password</h5>
                <form id="form-password">
                    <div class="form-group">
                        <label for="reset_new_password">New Password</label>
                            <input type="password" class="form-control" id="reset_new_password" name="reset_new_password" 
                                   placeholder="Enter new password" required>
                    </div>
                    <div class="form-group">
                        <label for="reset_confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" id="reset_confirm_password" name="reset_confirm_password" 
                                   placeholder="Confirm new password" required>
                    </div>
                        <button type="submit" class="btn btn-success" id="password-btn">
                            <i class="fas fa-save btn-icon"></i> <span class="btn-text">Reset Password</span>
                        </button>
                </form>
            </div>

            <!-- Step 6: Done -->
            <div class="step-section" id="step-done" style="display:none;">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Your password has been reset successfully! 
                        <a href="index.php" class="link-btn">Go to Login</a>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button class="link-btn" id="back-to-login">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </button>
            </div>
            </div>
        </div>
    </div>

    <!-- Footer -->


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
// Skeleton Loading Handler
$(document).ready(function() {
    const skeletonLoader = document.getElementById('skeleton-loader');
    const header = document.getElementById('header');
    const mainContainer = document.getElementById('main-container');
    const footer = document.getElementById('footer');
    
    // Function to hide skeleton and show content
    function showContent() {
        // Fade out skeleton loader
        skeletonLoader.classList.add('fade-out');
        
        // After skeleton fades out, show main content
        setTimeout(() => {
            skeletonLoader.style.display = 'none';
            
            // Show header, main container, and footer with animations
            header.classList.add('show');
            mainContainer.classList.add('show');
            footer.classList.add('show');
        }, 500);
    }
    
    // Show content after a short delayhhh
    setTimeout(showContent, 1000);
});

// LOGIN HANDLER
$('#loginForm').submit(function(e) {
    e.preventDefault();
    $('#login-error').hide();
    $('#login-success').hide();
    var reg = $('#login_regnumber').val();
    var pass = $('#login_password').val();
    var $btn = $('#login-btn');
    $btn.addClass('loading').prop('disabled', true);
    $.post('reset_password.php', { step: 'login', regnumber: reg, password: pass }, function(res) {
        $btn.removeClass('loading').prop('disabled', false);
        if(res.status === 'success') {
            $('#login-success').text(res.message).fadeIn();
            setTimeout(function(){ window.location.href = 'Students/index.php'; }, 1200);
        } else {
            $('#login-error').text(res.message).fadeIn();
        }
    }, 'json').fail(function(xhr){
        $btn.removeClass('loading').prop('disabled', false);
        $('#login-error').text('Server error.').fadeIn();
    });
});

$('#show-forgot').click(function(){
    $('#login-section').hide();
    $('#forgot-section').show();
    setStep('regnumber');
});

$('#back-to-login').click(function(){
    $('#forgot-section').hide();
    $('#login-section').show();
});

function setStep(step) {
    var steps = ['regnumber', 'nid', 'auth-method', 'code', 'password'];
    steps.forEach(function(s, idx) {
        var $section = $('#step-' + s);
        $section.removeClass('active completed');
                
                if (s === step) {
                    // Current step - show and make active
            $section.addClass('active');
                    $section.show();
            $section.find('input,button').prop('disabled', false);
        } else {
                    // All other steps - hide completely
            $section.hide();
                    $section.find('input,button').prop('disabled', true);
        }
    });
    $('#reset-error').hide();
    $('#reset-success').hide();
}

$(function() {
    setStep('regnumber');
            
    // Step 1: Regnumber
    $('#form-regnumber').submit(function(e) {
        e.preventDefault();
        var $btn = $('#regnumber-btn');
        $btn.addClass('loading').prop('disabled', true);
        showSpinner(true);
        $.post('reset_password.php', {step:'regnumber', regnumber:$('#reset_regnumber').val()}, function(res) {
            $btn.removeClass('loading').prop('disabled', false);
            showSpinner(false);
            if(res.status==='success') {
                setStep('nid');
            } else showError(res.message);
        },'json').fail(function(xhr){ $btn.removeClass('loading').prop('disabled', false); showSpinner(false); console.log('AJAX error:', xhr); showError(xhr.responseText || 'Server error.'); });
    });
            
    // Step 2: NID
    $('#form-nid').submit(function(e) {
        e.preventDefault();
        var $btn = $('#nid-btn');
        $btn.addClass('loading').prop('disabled', true);
        showSpinner(true);
        $.post('reset_password.php', {step:'nid', nid:$('#reset_nid').val()}, function(res) {
            $btn.removeClass('loading').prop('disabled', false);
            showSpinner(false);
            if(res.status==='success') {
                $('#show-email').text(res.email);
                $('#show-phone').text(res.phone);
                setStep('auth-method');
            } else showError(res.message);
        },'json').fail(function(xhr){ $btn.removeClass('loading').prop('disabled', false); showSpinner(false); console.log('AJAX error:', xhr); showError(xhr.responseText || 'Server error.'); });
    });
            
    // Step 3: Auth Method
    $('#form-auth-method').submit(function(e) {
        e.preventDefault();
        var $btn = $('#auth-method-btn');
        $btn.addClass('loading').prop('disabled', true);
        showSpinner(true);
        var method = $('input[name="auth_method"]:checked').val();
        $.post('reset_password.php', {step:'send_code', method:method}, function(res) {
            $btn.removeClass('loading').prop('disabled', false);
            showSpinner(false);
            if(res.status==='success') {
                showSuccess(res.message);
                if(res.debug_phone) {
                    console.log('Phone number sent to Pindo:', res.debug_phone);
                }
                setStep('code');
            } else {
                if(res.debug_phone) {
                    console.log('Phone number sent to Pindo:', res.debug_phone);
                }
                showError(res.message);
            }
        },'json').fail(function(xhr){
            $btn.removeClass('loading').prop('disabled', false);
            showSpinner(false);
            let response = {};
            try { response = JSON.parse(xhr.responseText); } catch (e) {}
            if (response.debug_phone) {
                console.log('Phone number sent to Pindo:', response.debug_phone);
            }
            console.log('AJAX error:', xhr);
            showError(xhr.responseText || 'Server error.');
        });
    });
            
    // Step 4: Code
    $('#form-code').submit(function(e) {
        e.preventDefault();
        var $btn = $('#code-btn');
        $btn.addClass('loading').prop('disabled', true);
        showSpinner(true);
        $.post('reset_password.php', {step:'verify_code', code:$('#reset_code').val()}, function(res) {
            $btn.removeClass('loading').prop('disabled', false);
            showSpinner(false);
            if(res.status==='success') {
                setStep('password');
            } else showError(res.message);
        },'json').fail(function(xhr){ $btn.removeClass('loading').prop('disabled', false); showSpinner(false); console.log('AJAX error:', xhr); showError(xhr.responseText || 'Server error.'); });
    });
            
    // Step 5: Password
    $('#form-password').submit(function(e) {
        e.preventDefault();
        var $btn = $('#password-btn');
        var pass = $('#reset_new_password').val();
        var conf = $('#reset_confirm_password').val();
        if(pass.length < 4) { showError('Password too short.'); return; }
        if(pass !== conf) { showError('Passwords do not match.'); return; }
        $btn.addClass('loading').prop('disabled', true);
        showSpinner(true);
        $.post('reset_password.php', {step:'reset_password', password:pass, confirm_password:conf}, function(res) {
            $btn.removeClass('loading').prop('disabled', false);
            showSpinner(false);
            if(res.status==='success') {
                $('#step-done').show();
                Swal.fire({
                    icon: 'success',
                    title: 'Password Reset Successful',
                    text: 'You can now log in with your new password!',
                    timer: 3000,
                    showConfirmButton: false
                });
                setTimeout(function(){ location.reload(); }, 3000);
            } else showError(res.message);
        },'json').fail(function(xhr){ $btn.removeClass('loading').prop('disabled', false); showSpinner(false); console.log('AJAX error:', xhr); showError(xhr.responseText || 'Server error.'); });
    });
});

function showError(msg) {
    $('#reset-error').text(msg).fadeIn();
}

function showSuccess(msg) {
    $('#reset-success').text(msg).fadeIn();
}

function showSpinner(show) {
            if(show) $('.spinner-container').show();
            else $('.spinner-container').hide();
}
</script>
</body>
</html> 
