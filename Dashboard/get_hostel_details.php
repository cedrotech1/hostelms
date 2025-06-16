<?php
include('connection.php');
include('./includes/auth.php');

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid hostel ID');
    }

    $hostel_id = (int)$_GET['id'];
    
    // Get hostel details
    $query = "SELECT h.*, c.name as campus_name 
              FROM hostels h 
              JOIN campuses c ON h.campus_id = c.id 
              WHERE h.id = ?";
    $stmt = mysqli_prepare($connection, $query);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($connection));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $hostel_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception('Database error: ' . mysqli_error($connection));
    }
    
    $hostel = mysqli_fetch_assoc($result);
    
    if (!$hostel) {
        throw new Exception('Hostel not found');
    }
    
    echo json_encode([
        'success' => true,
        'hostel' => [
            'id' => $hostel['id'],
            'name' => $hostel['name'],
            'building_code' => $hostel['building_code'],
            'othernames' => $hostel['othernames'],
            'gender' => $hostel['gender'],
            'year' => $hostel['year'],
            'campus_id' => $hostel['campus_id'],
            'campus_name' => $hostel['campus_name']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 