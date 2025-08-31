<?php
include 'connection.php';

header('Content-Type: application/json');



$userID = $_SESSION['id'];

$query = "SELECT campus FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$user_campus = '';
if ($row = $result->fetch_assoc()) {
    $user_campus = $row['campus'];
}

// Check if user is logged in and has campus assigned
if (!isset($user_campus)) {
    echo "<script>alert('No campus assigned. Please contact administrator.'); window.location.href='index.php';</script>";
    exit();
}

// Check if user is logged in and has campus assigned
if (!isset($user_campus)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No campus assigned'
    ]);
    exit();
}

// $user_campus = $_SESSION['campus'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['wadden_id'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_POST['wadden_id']);
    
    // Get wadden data (only for user's campus)
    $query = "SELECT u.*, GROUP_CONCAT(wh.hostel_id) as hostel_ids
              FROM users u 
              LEFT JOIN wadden_hostels wh ON u.id = wh.wadden_id 
              WHERE u.id = $wadden_id AND u.role = 'wadden' AND u.campus = '$user_campus'
              GROUP BY u.id";
    
    $result = mysqli_query($connection, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $wadden = mysqli_fetch_assoc($result);
        
        // Get hostel names for display
        if ($wadden['hostel_ids']) {
            $hostel_ids = $wadden['hostel_ids'];
            $hostel_query = "SELECT GROUP_CONCAT(name) as hostel_names FROM hostels WHERE id IN ($hostel_ids)";
            $hostel_result = mysqli_query($connection, $hostel_query);
            if ($hostel_result) {
                $hostel_data = mysqli_fetch_assoc($hostel_result);
                $wadden['assigned_hostels'] = $hostel_data['hostel_names'];
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $wadden
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Wadden not found or not authorized'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
?> 