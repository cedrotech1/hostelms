<?php
include('connection.php');
include('./includes/auth.php');

// Check if user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle file upload (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && $isAdmin) {
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    $fileSize = $file['size'];

    if ($fileError === 0) {
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt === 'pdf') {
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileNewName = uniqid('', true) . '.' . $fileExt;
            $fileDestination = $uploadDir . $fileNewName;

            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                $fileLink = $fileDestination;

                $stmt = $connection->prepare("INSERT INTO uploaded_files (file_name, file_link) VALUES (?, ?)");
                $stmt->bind_param("ss", $fileName, $fileLink);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>File uploaded and link saved successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error saving the file link to the database.</div>";
                }
                $stmt->close();
            } else {
                echo "<div class='alert alert-danger'>Error uploading the file.</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>Only PDF files are allowed!</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error occurred during file upload.</div>";
    }
}

// Handle file deletion (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_file_id']) && $isAdmin) {
    $fileId = intval($_POST['delete_file_id']);

    $stmt = $connection->prepare("SELECT file_link FROM uploaded_files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    $stmt->close();

    if ($file) {
        // Delete the file from the server
        if (unlink($file['file_link'])) {
            // Remove the file record from the database
            $stmt = $connection->prepare("DELETE FROM uploaded_files WHERE id = ?");
            $stmt->bind_param("i", $fileId);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>File deleted successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error deleting the file from the database.</div>";
            }
            $stmt->close();
        } else {
            echo "<div class='alert alert-danger'>Error deleting the file from the server.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>File not found.</div>";
    }
}

// Fetch uploaded file links from the database
$sql = "SELECT * FROM uploaded_files ORDER BY uploaded_at DESC";
$result = $connection->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UR</title>
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

    <?php include("./includes/header.php"); ?>
    <?php include("./includes/menu.php"); ?>

    <main id="main" class="main container py-5">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">Upload Your PDF Document</h1>
                        <?php if ($isAdmin): ?>
                            <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                                <div class="mb-3">
                                    <input type="file" name="file" accept="application/pdf" class="form-control" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="text-danger text-center">Only admins can upload files.</p>
                        <?php endif; ?>

                        <div class="file-list">
                            <h2 class="text-center mb-3">Uploaded Files</h2>
                            <?php
                            if ($result->num_rows > 0) {
                                echo '<ul class="list-group">';
                                while ($row = $result->fetch_assoc()) {
                                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                    echo '<a href="' . htmlspecialchars($row['file_link']) . '" target="_blank">' . htmlspecialchars($row['file_name']) . '</a>';
                                    if ($isAdmin) {
                                        echo '<form action="" method="POST" style="margin: 0;">';
                                        echo '<input type="hidden" name="delete_file_id" value="' . $row['id'] . '">';
                                        echo '<button type="submit" class="btn btn-danger btn-sm">Delete</button>';
                                        echo '</form>';
                                    }
                                    echo '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo "<p class='text-center text-muted'>No files uploaded yet.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include("./includes/footer.php"); ?>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>

<?php
$connection->close();
?>
