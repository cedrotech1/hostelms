<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

// Include DB connection
include_once 'connection.php';
// Include email functions
include_once 'email_functions.php';

function sendJson($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit();
}

// Step 1: Check regnumber
if (isset($_POST['step']) && $_POST['step'] === 'regnumber') {
    $reg = mysqli_real_escape_string($connection, $_POST['regnumber'] ?? '');
    $q = mysqli_query($connection, "SELECT * FROM info WHERE regnumber='$reg'");
    if (mysqli_num_rows($q) === 1) {
        $_SESSION['reset_regnumber'] = $reg;
        sendJson('success', 'Registration number found. Please enter your NID.');
    } else {
        sendJson('error', 'Registration number not found.');
    }
}

// Step 2: Check NID
if (isset($_POST['step']) && $_POST['step'] === 'nid') {
    if (!isset($_SESSION['reset_regnumber'])) sendJson('error', 'Session expired. Start again.');
    $nid = mysqli_real_escape_string($connection, $_POST['nid'] ?? '');
    $reg = $_SESSION['reset_regnumber'];
    $q = mysqli_query($connection, "SELECT * FROM info WHERE regnumber='$reg' AND nid='$nid'");
    if (mysqli_num_rows($q) === 1) {
        $_SESSION['reset_nid'] = $nid;
        $user = mysqli_fetch_assoc($q);
        // Store email/phone/name for next step
        $_SESSION['reset_email'] = $user['email'];
        $_SESSION['reset_phone'] = $user['phone'];
        $_SESSION['reset_name'] = $user['names'];
        sendJson('success', 'NID matched. Choose authentication method.', [
            'email' => $user['email'],
            'phone' => $user['phone']
        ]);
    } else {
        sendJson('error', 'NID does not match.');
    }
}

// Step 3: Send authentication code
if (isset($_POST['step']) && $_POST['step'] === 'send_code') {
    if (!isset($_SESSION['reset_regnumber']) || !isset($_SESSION['reset_nid'])) sendJson('error', 'Session expired. Start again.');
    $method = $_POST['method'] ?? '';
    $code = rand(100000, 999999);
    $_SESSION['reset_code'] = $code;
    $name = $_SESSION['reset_name'] ?? '';
    $email = $_SESSION['reset_email'] ?? '';
    $phone = $_SESSION['reset_phone'] ?? '';
    $success = false;
    if ($method === 'email') {
        // Send code via email
        $result = sendResetPasswordEmail($email, $name, $code);
        if ($result === true) {
            $success = true;
        } else {
            sendJson('error', 'Failed to send email: ' . $result);
        }
    } elseif ($method === 'sms') {
        // Remove leading plus sign if present
        
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+250' . substr($phone, 1);
            }
        }
     
        $sms_data = [
            'to' => $phone,
            'text' => "Your password reset code is: $code",
            'sender' => 'PindoTest'
        ];
        $ch = curl_init('https://api.pindo.io/v1/sms/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE4MzcxNzUzMTIsImlhdCI6MTc0MjQ4MDkxMiwiaWQiOiJ1c2VyXzAxSlBTWjlDMTZCTUtZQzZLSkdWRkhQOTBNIiwicmV2b2tlZF90b2tlbl9jb3VudCI6MH0.KjgMZ0ht_NhUbil_3kIgHHByJSokufd2IZdC9-PYeXdkJkan4Rv8DMi0jlHXfZnyh_52bOizk9nTR3QOEBU5ZA',
            'Content-Type: application/json'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode >= 200 && $httpCode < 300 ) {
            $success = true;
        } else {
            sendJson('error', 'Failed to send SMS. HTTP code: ' . $httpCode . ' Response: ' . $response, ['debug_phone' => $phone]);
        }
    } else {
        sendJson('error', 'Invalid authentication method.');
    }
    if ($success) {
        sendJson('success', 'Authentication code sent. Please check your ' . ($method === 'email' ? 'email' : 'phone') . '.', ['debug_phone' => $phone]);
    }
}

// Step 4: Verify code
if (isset($_POST['step']) && $_POST['step'] === 'verify_code') {
    if (!isset($_SESSION['reset_code'])) sendJson('error', 'Session expired. Start again.');
    $user_code = $_POST['code'] ?? '';
    if ($user_code == $_SESSION['reset_code']) {
        $_SESSION['reset_verified'] = true;
        sendJson('success', 'Code verified. You can now reset your password.');
    } else {
        sendJson('error', 'Invalid code.');
    }
}

// Step 5: Reset password
if (isset($_POST['step']) && $_POST['step'] === 'reset_password') {
    if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) sendJson('error', 'Session expired. Start again.');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 4) sendJson('error', 'Password too short.');
    if ($password !== $confirm) sendJson('error', 'Passwords do not match.');
    $reg = $_SESSION['reset_regnumber'];
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $q = mysqli_query($connection, "UPDATE info SET password='$hashed' WHERE regnumber='$reg'");
    if ($q) {
        // Clear session
        unset($_SESSION['reset_regnumber'], $_SESSION['reset_nid'], $_SESSION['reset_email'], $_SESSION['reset_phone'], $_SESSION['reset_name'], $_SESSION['reset_code'], $_SESSION['reset_verified']);
        sendJson('success', 'Password reset successful! You can now log in.');
    } else {
        sendJson('error', 'Failed to update password.');
    }
}

// LOGIN endpoint
if (isset($_POST['step']) && $_POST['step'] === 'login') {
    $reg = mysqli_real_escape_string($connection, $_POST['regnumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $q = mysqli_query($connection, "SELECT * FROM info WHERE regnumber='$reg'");
    if (!$q) {
        sendJson('error', 'Database error: ' . mysqli_error($connection));
    }
    if (mysqli_num_rows($q) === 1) {
        $user = mysqli_fetch_assoc($q);
        if (password_verify($password, $user['password'])) {
            // Set session variables as needed
            $_SESSION['student_id'] = $user['id'];
            $_SESSION['student_regnumber'] = $user['regnumber'];
            $_SESSION['student_name'] = $user['names'];
            $_SESSION['student_email'] = $user['email'];
            $_SESSION['student_campus'] = $user['campus'];
            $_SESSION['student_college'] = $user['college'];
            $_SESSION['student_school'] = $user['school'];
            $_SESSION['student_program'] = $user['program'];
            $_SESSION['student_year'] = $user['yearofstudy'];
            $_SESSION['student_gender'] = $user['gender'];
            $_SESSION['student_status'] = $user['status'];
            $_SESSION['student_intake'] = $user['intake'];
            $_SESSION['student_disability'] = $user['disability'];
            sendJson('success', 'Login successful! Redirecting...');
        } else {
            sendJson('error', 'Invalid password.');
        }
    } else {
        sendJson('error', 'Registration number not found.');
    }
}

// Default: Invalid request
sendJson('error', 'Invalid request.'); 