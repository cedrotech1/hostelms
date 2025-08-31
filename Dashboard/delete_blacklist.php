<?php
include('connection.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Delete the blacklist entry
    $stmt = $connection->prepare("DELETE FROM blacklist WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Student removed from blacklist successfully.'); window.location.href='blacklist.php';</script>";
    } else {
        echo "<script>alert('Error removing student from blacklist. Please try again.'); window.location.href='blacklist.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request. Please try again.'); window.location.href='blacklist.php';</script>";
}
?>
