<?php
include('connection.php');
header('Content-Type: application/json');

$response = ['success' => false, 'total' => 0];

if (isset($_GET['hostel_id'])) {
    $hostel_id = (int)$_GET['hostel_id'];
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
    
    $query = "SELECT COUNT(*) as total FROM rooms WHERE hostel_id = $hostel_id";
    if (!empty($search)) {
        $query .= " AND room_code LIKE '%$search%'";
    }
    
    $result = mysqli_query($connection, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['total'] = (int)$row['total'];
    }
}

echo json_encode($response); 