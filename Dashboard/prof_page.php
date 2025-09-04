<?php
// session_start();
require_once 'connection.php'; // your DB connection

// Example: user role from session
$role = $_SESSION['role']; // default to 'student' if not set

// Fetch manuals for user's role
$stmt = $connection->prepare("SELECT * FROM uploaded_files WHERE role=? ORDER BY uploaded_at DESC");
$stmt->bind_param("s", $role);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Manuals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>
    <!-- Main Content -->
    <main id="main" class="main">
        <?php
        include("disability_proofs.php");

        ?>

    </main>
    <?php
    include("./includes/footer.php");
    ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
