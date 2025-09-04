<?php
include 'connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit();
}

$userID = $_SESSION['id'];

// Get user's role and campus
$query = "SELECT role, campus FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not found'
    ]);
    exit();
}

$user = $result->fetch_assoc();
$is_hq = empty($user['campus']); // HQ users have no campus assigned
$user_campus = $user['campus'];

// Only allow HQ users or users with campus to proceed
if (!$is_hq && empty($user_campus)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No campus assigned. Please contact administrator.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['wadden_id'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_POST['wadden_id']);
    
    // Base query to get wadden data
    $query = "SELECT u.*, c.name as campus_name, GROUP_CONCAT(wh.hostel_id) as hostel_ids
              FROM users u 
              LEFT JOIN campuses c ON u.campus = c.id
              LEFT JOIN wadden_hostels wh ON u.id = wh.wadden_id 
              WHERE u.id = $wadden_id AND u.role = 'wadden'";
    
    // Add campus restriction for non-HQ users
    if (!$is_hq) {
        $query .= " AND u.campus = '$user_campus'";
    }
    
    $query .= " GROUP BY u.id";
    
    $result = mysqli_query($connection, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $wadden = mysqli_fetch_assoc($result);
        $wadden_campus = $wadden['campus'];
        
        // Get assigned hostels
        $hostel_ids = !empty($wadden['hostel_ids']) ? explode(',', $wadden['hostel_ids']) : [];
        
        // Get all hostels for the wadden's campus
        $hostels_query = "SELECT h.*, c.name as campus_name 
                         FROM hostels h 
                         JOIN campuses c ON h.campus_id = c.id 
                         WHERE h.campus_id = '$wadden_campus' 
                         ORDER BY h.name";
        
        $hostels_result = mysqli_query($connection, $hostels_query);
        $hostels = [];
        
        if ($hostels_result) {
            while ($hostel = mysqli_fetch_assoc($hostels_result)) {
                $hostels[] = [
                    'id' => $hostel['id'],
                    'name' => $hostel['name'],
                    'building_code' => $hostel['building_code'],
                    'campus_name' => $hostel['campus_name'],
                    'selected' => in_array($hostel['id'], $hostel_ids)
                ];
            }
        }
        
        // Get all campuses for reference (for HQ users)
        $campuses = [];
        if ($is_hq) {
            $campuses_query = "SELECT * FROM campuses ORDER BY name";
            $campuses_result = mysqli_query($connection, $campuses_query);
            while ($campus = mysqli_fetch_assoc($campuses_result)) {
                $campuses[] = [
                    'id' => $campus['id'],
                    'name' => $campus['name']
                ];
            }
        }
        
        // Prepare response
        $response = [
            'status' => 'success',
            'data' => [
                'id' => $wadden['id'],
                'names' => $wadden['names'],
                'email' => $wadden['email'],
                'phone' => $wadden['phone'],
                'campus' => $wadden_campus,
                'campus_name' => $wadden['campus_name'] ?? 'N/A',
                'hostels' => $hostels,
                'campuses' => $campuses
            ]
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Wadden not found or not authorized.'
        ]);
    }
}
?>