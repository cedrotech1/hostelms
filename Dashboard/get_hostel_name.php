<?php
include('connection.php');
header('Content-Type: application/json');

$response = ['success' => false, 'hostel_name' => ''];

if (isset($_GET['hostel_id'])) {
    $hostel_id = (int)$_GET['hostel_id'];
    
    $query = "SELECT name FROM hostels WHERE id = $hostel_id";
    $result = mysqli_query($connection, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['hostel_name'] = $row['name'];
    }
}

echo json_encode($response); 