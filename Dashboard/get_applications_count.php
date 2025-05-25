<?php
include('connection.php');

$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($connection, $_GET['status']) : '';

// Build the search condition
$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (i.regnumber LIKE '%$search%' OR i.names LIKE '%$search%' OR i.email LIKE '%$search%' OR r.room_code LIKE '%$search%')";
}

// Build the status condition
$status_condition = '';
if (!empty($status)) {
    $status_condition = " AND a.status = '$status'";
}

// Get total count of applications
$query = "SELECT COUNT(*) as total 
          FROM applications a
          JOIN info i ON i.regnumber = a.regnumber
          JOIN rooms r ON r.id = a.room_id
          WHERE 1=1 $search_condition $status_condition";

$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode(['total' => (int)$row['total']]);
?> 