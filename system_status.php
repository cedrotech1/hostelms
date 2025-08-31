<?php
include('connection.php');

$id = 1; 
$query = "SELECT id, status, exp_date, exam_validity, accademic_year, semester, allow_message, time 
          FROM system 
          WHERE id = $id";
$result = mysqli_query($connection, $query);

$status = null; // default
if ($row = mysqli_fetch_assoc($result)) {
    $status = strtolower($row['status']); // store status in variable
}
?>
