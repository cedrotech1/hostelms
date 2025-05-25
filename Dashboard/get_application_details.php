<?php
include('connection.php');

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT 
                a.*, 
                i.names, 
                i.gender, 
                i.yearofstudy, 
                i.email, 
                i.phone,
                i.campus,
                i.college,
                i.school,
                i.program,
                r.room_code,
                h.name as hostel_name
              FROM applications a
              JOIN info i ON i.regnumber = a.regnumber
              JOIN rooms r ON r.id = a.room_id
              JOIN hostels h ON h.id = r.hostel_id
              WHERE a.id = $id";
    
    $result = mysqli_query($connection, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $application = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'application' => $application
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Application not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing application ID'
    ]);
}
?> 