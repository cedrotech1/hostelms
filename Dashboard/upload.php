<?php
include('connection.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_FILES['croppedImage']) && $_FILES['croppedImage']['error'] == 0) {
    $pname = mysqli_real_escape_string($connection, $_POST['pname']);
    $location = mysqli_real_escape_string($connection, $_POST['location']);

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $imageName = basename($_FILES['croppedImage']['name']);
    $uploadFile = $uploadDir . $imageName;

    if (move_uploaded_file($_FILES['croppedImage']['tmp_name'], $uploadFile)) {
        // Insert product into the database
        $ok = mysqli_query($connection, "INSERT INTO `product`(`pid`, `pname`, `description`, `image`, `location`) 
                VALUES (null, '$pname', '$pname', '$imageName', '$location')");

        if (!$ok) {
            echo "Failed to insert product: " . mysqli_error($connection);
        } else {
            echo "Product inserted successfully.";
        }
    } else {
        echo "Failed to upload image.";
    }
} else {
    echo "No image uploaded or upload error.";
}
?>
