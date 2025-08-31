<?php
// db.php — Database connection
$host = "localhost";
$user = "root";      // change if needed
$pass = "";          // change if needed
$dbname = "hostel2"; // change to your DB name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// If form submitted
if (isset($_POST["submit"])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($fileTmpPath, "r");

        if ($handle !== FALSE) {
            // Skip header row
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $room_code = $conn->real_escape_string($data[1]);
                $number_of_beds = (int)$data[2];
                $hostel_id = (int)$data[3];

                $remain = $number_of_beds;
                $status = 'published';
                $createdBy = 1;
                $updatedBy = 1;

                $sql = "INSERT INTO rooms (room_code, number_of_beds, hostel_id, remain, status, createdBy, updatedBy, createdAt, updatedAt)
                        VALUES ('$room_code', $number_of_beds, $hostel_id, $remain, '$status', $createdBy, $updatedBy, NOW(), NOW())";

                $conn->query($sql);
            }
            fclose($handle);
            echo "<p>CSV file uploaded and data inserted successfully!</p>";
        }
    } else {
        echo "<p>Error uploading file.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Rooms CSV</title>
</head>
<body>
    <h2>Upload Rooms CSV</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <input type="submit" name="submit" value="Upload">
    </form>
</body>
</html>
