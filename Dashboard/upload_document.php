<?php
require_once 'connection.php'; // your DB connection

// Handle File Upload
if(isset($_POST['upload'])){
    $role = $_POST['role'];
    $file = $_FILES['file'];

    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if($fileExt !== 'pdf'){
        $error = "Only PDF files are allowed!";
    } else {
        $uploadDir = 'manuals/'; 
        if(!is_dir($uploadDir)){
            mkdir($uploadDir, 0777, true);
        }

        $filePath = $uploadDir . time() . '_' . basename($fileName);

        if(move_uploaded_file($fileTmp, $filePath)){
            $stmt = $connection->prepare("INSERT INTO uploaded_files (file_name, file_link, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fileName, $filePath, $role);
            $stmt->execute();
            $success = "File uploaded successfully!";
        } else {
            $error = "Failed to upload file.";
        }
    }
}

// Handle File Deletion
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $fileRes = $connection->query("SELECT file_link FROM uploaded_files WHERE id=$id");
    if($fileRes->num_rows){
        $fileRow = $fileRes->fetch_assoc();
        if(file_exists($fileRow['file_link'])){
            unlink($fileRow['file_link']); // delete file
        }
        $connection->query("DELETE FROM uploaded_files WHERE id=$id");
        $success = "File deleted successfully!";
    }
}

// Fetch All Uploaded Files
$result = $connection->query("SELECT * FROM uploaded_files ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User Manuals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <!-- <h1 class="">Admin: Manage User Manuals</h1> -->

    <!-- Alerts -->
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="card mb-4">
        <div class="card-header">Upload Manual</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <select name="role" class="form-select" required>
                        <option value="">Select Role</option>
                        <option value="warefare">Director of Welfare</option>
                        <option value="head_quarter">Head Quarter</option>
                        <option value="wadden">Warden</option>
                        <option value="information_modifier">Admin</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="file" name="file" class="form-control" accept="application/pdf" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="upload" class="btn btn-primary w-100">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Uploaded Files Table -->
    <div class="card">
        <div class="card-header">Uploaded Manuals</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>File Name</th>
                        <th>Role</th>
                        <th>Uploaded At</th>
                        <th>Download</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['file_name']) ?></td>
                            <td><?= htmlspecialchars($row['role']) ?></td>
                            <td><?= $row['uploaded_at'] ?></td>
                            <td><a href="<?= $row['file_link'] ?>" class="btn btn-success btn-sm" download>Download</a></td>
                            <td>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No manuals uploaded yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
