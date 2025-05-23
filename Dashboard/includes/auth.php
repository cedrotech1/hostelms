<?php
// session_start();

/**
 * Function to protect pages based on user roles.
 *
 * @param array $allowedRoles Array of roles allowed to access the page.
 */
function checkUserRole(array $allowedRoles)
{
    // Check if the user's role exists in the session
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // Redirect to index.php if the role is not allowed
        echo "<script>window.location.href='./index.php'</script>";
        exit; // Stop further script execution
    }
}
?>
