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

if ($wadden_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid wadden ID']);
    exit;
}

// Start transaction
mysqli_begin_transaction($connection);

try {
    // First, delete from wadden_hostels table
    $query1 = "DELETE FROM wadden_hostels WHERE wadden_id = ?";
    $stmt1 = mysqli_prepare($connection, $query1);
    mysqli_stmt_bind_param($stmt1, 'i', $wadden_id);
    
    if (!mysqli_stmt_execute($stmt1)) {
        throw new Exception('Failed to delete wadden-hostel associations');
    }
    
    // Then, delete the wadden
    $query2 = "DELETE FROM users WHERE id = ? AND role = 'wadden'";
    $stmt2 = mysqli_prepare($connection, $query2);
    mysqli_stmt_bind_param($stmt2, 'i', $wadden_id);
    
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception('Failed to delete wadden');
    }
    
    // Commit the transaction
    mysqli_commit($connection);
    echo json_encode(['success' => true, 'message' => 'Wadden deleted successfully']);
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($connection);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
mysqli_close($connection);
?>
