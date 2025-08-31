<?php
// Include database connection
include('connection.php');

// Check if userId is provided in the URL
if (isset($_GET['userId'])) {
    // Sanitize the userId input to prevent SQL injection
    $userId = mysqli_real_escape_string($connection, $_GET['userId']);

    // Update the user's active status to 0 (inactive)
    $query = "UPDATE users SET active = 0 WHERE id = $userId";
    $connection->query($query);
    header('Location: ' . 'add_user.php');
    exit;
   
} else {
    // userId is not provided, redirect back to the page where the deactivation was triggered
    
    header('Location: ' . 'users.php');
    exit;
}
?>
