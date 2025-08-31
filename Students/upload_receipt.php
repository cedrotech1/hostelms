<?php
session_start();
include("connection.php");

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id']) && isset($_FILES['receipt'])) {
    $application_id = (int)$_POST['application_id'];
    $student_regnumber = $_SESSION['student_regnumber'];
    
    // Get receipt number and date of payment from form
    $receipt_number = trim($_POST['receipt_number'] ?? '');
    $date_of_payment = trim($_POST['date_of_payment'] ?? '');
    
    // Validate required fields
    if (empty($receipt_number)) {
        $_SESSION['error_message'] = "Receipt number is required.";
        header("Location: index.php");
        exit();
    }
    
    if (empty($date_of_payment)) {
        $_SESSION['error_message'] = "Date of payment is required.";
        header("Location: index.php");
        exit();
    }

    // Check if receipt number already exists for this student
    $check_receipt_query = "SELECT id FROM applications WHERE ReceptNumber = ? AND regnumber = ?";
    $check_receipt_stmt = $connection->prepare($check_receipt_query);
    $check_receipt_stmt->bind_param("ss", $receipt_number, $student_regnumber);
    $check_receipt_stmt->execute();
    $check_receipt_stmt->store_result();
    if ($check_receipt_stmt->num_rows > 0) {
        $_SESSION['error_message'] = "This receipt number has already been used by you. Please use a unique receipt number.";
        header("Location: index.php");
        exit();
    }
    $check_receipt_stmt->close();
    
    // Verify that this application belongs to the student
    $verify_query = "SELECT * FROM applications WHERE id = ? AND regnumber = ?";
    $verify_stmt = $connection->prepare($verify_query);
    $verify_stmt->bind_param("is", $application_id, $student_regnumber);
    $verify_stmt->execute();
    $application = $verify_stmt->get_result()->fetch_assoc();
    
    if (!$application) {
        $_SESSION['error_message'] = "Invalid application or receipt already uploaded.". $application_id."".$student_regnumber;
        header("Location: index.php");
        exit();
    }
    
    // Handle file upload
    $file = $_FILES['receipt'];
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error_message'] = "Invalid file type. Please upload JPG, PNG, or PDF.";
        header("Location: index.php");
        exit();
    }
    
    if ($file['size'] > $max_size) {
        $_SESSION['error_message'] = "File is too large. Maximum size is 2MB.";
        header("Location: index.php");
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = "./uploads/receipts/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $student_regnumber . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update application with receipt information including new fields
        $current_time = date('Y-m-d H:i:s');
        $update_query = "UPDATE applications SET 
                        slep = ?,
                        ReceptNumber = ?,
                        Date_of_payment = ?,
                        status = 'paid',
                        updated_at = '$current_time'
                        WHERE id = ?";
        $update_stmt = $connection->prepare($update_query);
        $update_stmt->bind_param("sssi", $filename, $receipt_number, $date_of_payment, $application_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Receipt uploaded successfully. Your application is now pending payment verification.";
        } else {
            // If update fails, delete the uploaded file
            unlink($filepath);
            $_SESSION['error_message'] = "Error updating application. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "Error uploading file. Please try again.";
    }
    
    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?> 