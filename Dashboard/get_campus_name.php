<?php
include('connection.php');
header('Content-Type: application/json');

$response = ['success' => false, 'campus_name' => ''];

if (isset($_GET['campus_id'])) {
    $campus_id = (int)$_GET['campus_id'];
    
    $query = "SELECT name FROM campuses WHERE id = $campus_id";
    $result = mysqli_query($connection, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['campus_name'] = $row['name'];
    }
}

echo json_encode($response); 