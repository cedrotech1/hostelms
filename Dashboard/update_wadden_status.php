<?php
include 'connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has permission
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$wadden_id = isset($_POST['wadden_id']) ? intval($_POST['wadden_id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

if ($wadden_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid wadden ID']);
    exit;
}

// Update the status
$query = "UPDATE users SET active = ? WHERE id = ? AND role = 'wadden'";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, 'ii', $status, $wadden_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

// Close connection
mysqli_close($connection);
?>
