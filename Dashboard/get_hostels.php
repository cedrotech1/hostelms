<?php
include('connection.php');
include('./includes/auth.php');

// Set JSON header
header('Content-Type: application/json');

try {
    // Validate campus_id
    if (!isset($_GET['campus_id']) || !is_numeric($_GET['campus_id'])) {
        throw new Exception('Invalid campus ID');
    }

    $campus_id = (int)$_GET['campus_id'];
    
    // Verify campus exists
    $check_campus = mysqli_prepare($connection, "SELECT id FROM campuses WHERE id = ?");
    mysqli_stmt_bind_param($check_campus, "i", $campus_id);
    mysqli_stmt_execute($check_campus);
    $campus_result = mysqli_stmt_get_result($check_campus);
    
    if (mysqli_num_rows($campus_result) === 0) {
        throw new Exception('Campus not found');
    }
    
    // Check if it's a dropdown request
    if (isset($_GET['dropdown']) && $_GET['dropdown'] == 'true') {
        $query = "SELECT id, name FROM hostels WHERE campus_id = ? ORDER BY name";
        $stmt = mysqli_prepare($connection, $query);
        if (!$stmt) {
            throw new Exception('Database error: ' . mysqli_error($connection));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $campus_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            throw new Exception('Database error: ' . mysqli_error($connection));
        }
        
        $hostels = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $hostels[] = array(
                'id' => $row['id'],
                'name' => $row['name']
            );
        }
        
        echo json_encode($hostels);
    } else {
        // Regular table view request
        $query = "SELECT h.*, c.name as campus_name, 
                  creator.names as created_by, 
                  updater.names as updated_by,
                  DATE_FORMAT(h.createdAt, '%Y-%m-%d') as createdAt,
                  DATE_FORMAT(h.updatedAt, '%Y-%m-%d') as updatedAt
                  FROM hostels h 
                  LEFT JOIN campuses c ON h.campus_id = c.id 
                  LEFT JOIN users creator ON h.createdBy = creator.id 
                  LEFT JOIN users updater ON h.updatedBy = updater.id 
                  WHERE h.campus_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        if (!$stmt) {
            throw new Exception('Database error: ' . mysqli_error($connection));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $campus_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            throw new Exception('Database error: ' . mysqli_error($connection));
        }
        
        $hostels = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $hostels[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'building_code' => $row['building_code'],
                'othernames' => $row['othernames'],
                'gender' => $row['gender'],
                'year' => $row['year'],
                'campus_name' => $row['campus_name'],
                'college' => $row['college'],
                'school' => $row['school'],
                'intake' => $row['intake'],
                'disability' => $row['disability'],
                'status'=>$row['status'],
                'created_by' => $row['created_by'],
                'updated_by' => $row['updated_by'],
                'createdAt' => $row['createdAt'],
                'updatedAt' => $row['updatedAt']
            ];
        }
        
        echo json_encode($hostels);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
}
?> 