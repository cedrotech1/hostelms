<?php
include 'connection.php';

header('Content-Type: application/json');

// Get wadden's assigned hostels
$wadden_id = $_SESSION['id'];

// Check if user is a wadden
$check_role = "SELECT role FROM users WHERE id = ?";
$stmt = $connection->prepare($check_role);
$stmt->bind_param("i", $wadden_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['role'];

if ($user_role !== 'wadden') {
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Only waddens can access this data.'
    ]);
    exit();
}

try {
    // Get wadden's assigned hostel IDs
    $hostels_query = "SELECT h.id FROM hostels h
                      JOIN wadden_hostels wh ON h.id = wh.hostel_id
                      WHERE wh.wadden_id = ?";
    $stmt = $connection->prepare($hostels_query);
    $stmt->bind_param("i", $wadden_id);
    $stmt->execute();
    $assigned_hostels_result = $stmt->get_result();
    
    $assigned_hostel_ids = [];
    while ($row = $assigned_hostels_result->fetch_assoc()) {
        $assigned_hostel_ids[] = $row['id'];
    }
    
    if (empty($assigned_hostel_ids)) {
        echo json_encode([
            'success' => true,
            'html' => '<div class="alert alert-info">No hostels assigned to you.</div>',
            'data' => [],
            'stats' => ['total_occupants' => 0, 'available_beds' => 0, 'occupancy_rate' => 0],
            'hostel_stats' => []
        ]);
        exit();
    }
    
    $hostel_ids_str = implode(',', $assigned_hostel_ids);
    
    // Get detailed hostel statistics
    $hostel_stats_query = "SELECT 
                            h.id,
                            h.name AS hostel_name,
                            h.building_code,
                            COUNT(r.id) AS total_rooms,
                            SUM(r.number_of_beds) AS total_beds,
                            SUM(r.remain) AS available_beds,
                            SUM(r.number_of_beds - r.remain) AS occupied_beds,
                            ROUND((SUM(r.number_of_beds - r.remain) / SUM(r.number_of_beds)) * 100, 1) AS occupancy_rate,
                            COUNT(CASE WHEN r.remain = 0 THEN 1 END) AS full_rooms,
                            COUNT(CASE WHEN r.remain = r.number_of_beds THEN 1 END) AS empty_rooms,
                            COUNT(CASE WHEN r.remain > 0 AND r.remain < r.number_of_beds THEN 1 END) AS partial_rooms
                          FROM hostels h
                          LEFT JOIN rooms r ON h.id = r.hostel_id
                          WHERE h.id IN ($hostel_ids_str)
                          GROUP BY h.id, h.name, h.building_code
                          ORDER BY h.name";
    
    $hostel_stats_result = $connection->query($hostel_stats_query);
    $hostel_stats = [];
    while ($row = $hostel_stats_result->fetch_assoc()) {
        $hostel_stats[] = $row;
    }
    
    // Build WHERE clause for occupants
    $where = " WHERE h.id IN ($hostel_ids_str)";
    
    if (!empty($_POST['hostel'])) {
        $hostel_id = $connection->real_escape_string($_POST['hostel']);
        $where .= " AND h.id = '$hostel_id'";
    }
    
    if (!empty($_POST['room'])) {
        $room = $connection->real_escape_string($_POST['room']);
        $where .= " AND r.room_code LIKE '%$room%'";
    }
    
    if (!empty($_POST['search'])) {
        $search = $connection->real_escape_string($_POST['search']);
        $where .= " AND (i.names LIKE '%$search%' OR i.regnumber LIKE '%$search%')";
    }
    
    // Add room status filter
    if (!empty($_POST['room_status'])) {
        $room_status = $connection->real_escape_string($_POST['room_status']);
        switch ($room_status) {
            case 'full':
                $where .= " AND r.remain = 0";
                break;
            case 'empty':
                $where .= " AND r.remain = r.number_of_beds";
                break;
            case 'partial':
                $where .= " AND r.remain > 0 AND r.remain < r.number_of_beds";
                break;
            case 'has_occupants':
                $where .= " AND a.id IS NOT NULL";
                break;
            case 'no_occupants':
                $where .= " AND a.id IS NULL";
                break;
        }
    }

    // Get occupants data
    $query = "SELECT 
                h.id AS hostel_id,
                h.name AS hostel_name,
                h.building_code,
                r.room_code,
                r.number_of_beds,
                r.remain,
                i.regnumber,
                i.names,
                i.campus,
                i.college,
                i.school,
                i.yearofstudy,
                i.phone,
                i.gender,
                a.status AS application_status,
                a.created_at AS application_date
            FROM hostels h
            JOIN rooms r ON r.hostel_id = h.id
            LEFT JOIN applications a ON a.room_id = r.id
            LEFT JOIN info i ON a.regnumber = i.regnumber
            $where
            ORDER BY h.name, r.room_code, i.names";

    $result = $connection->query($query);

    if ($result) {
        $rows = [];
        $stats = [
            'total_occupants' => 0,
            'total_beds' => 0,
            'available_beds' => 0,
            'occupancy_rate' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            // Only show rows with an occupant
            if ($row['names'] && $row['regnumber']) {
                $rows[] = $row;
                $stats['total_occupants']++;
            }
            $stats['total_beds'] += $row['number_of_beds'];
            $stats['available_beds'] += $row['remain'];
        }
        
        if ($stats['total_beds'] > 0) {
            $stats['occupancy_rate'] = round((($stats['total_beds'] - $stats['available_beds']) / $stats['total_beds']) * 100, 1);
        }

        // Generate HTML
        $html = '';
        if (empty($rows)) {
            $html = '<div class="alert alert-info">No occupants found for the selected criteria.</div>';
        } else {
            // Create a single table with all occupants
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';
            $html .= '<thead class="table-dark">';
            $html .= '<tr>';
            $html .= '<th>Hostel</th>';
            $html .= '<th>Room</th>';
            $html .= '<th>Reg Number</th>';
            $html .= '<th>Name</th>';
            $html .= '<th>Gender</th>';
            $html .= '<th>College</th>';
            $html .= '<th>School</th>';
            $html .= '<th>Year</th>';
            $html .= '<th>Phone</th>';
            $html .= '<th>Status</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            
            foreach ($rows as $occupant) {
                $status_class = '';
                $status_text = $occupant['application_status'];
                
                switch ($occupant['application_status']) {
                    case 'approved':
                        $status_class = 'status-approved';
                        break;
                    case 'pending':
                        $status_class = 'status-pending';
                        break;
                    case 'paid':
                        $status_class = 'status-paid';
                        break;
                }
                
                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars($occupant['hostel_name']) . '</strong><br><small class="text-muted">(' . htmlspecialchars($occupant['building_code']) . ')</small></td>';
                $html .= '<td>' . htmlspecialchars($occupant['room_code']) . '</td>';
                $html .= '<td><strong>' . htmlspecialchars($occupant['regnumber']) . '</strong></td>';
                $html .= '<td>' . htmlspecialchars($occupant['names']) . '</td>';
                $html .= '<td>' . htmlspecialchars($occupant['gender']) . '</td>';
                $html .= '<td>' . htmlspecialchars($occupant['college']) . '</td>';
                $html .= '<td>' . htmlspecialchars($occupant['school']) . '</td>';
                $html .= '<td>' . htmlspecialchars($occupant['yearofstudy']) . '</td>';
                $html .= '<td>' . htmlspecialchars($occupant['phone']) . '</td>';
                $html .= '<td><span class="status-badge ' . $status_class . '">' . ucfirst($status_text) . '</span></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        echo json_encode([
            'success' => true,
            'html' => $html,
            'data' => $rows,
            'stats' => $stats,
            'hostel_stats' => $hostel_stats
        ]);
    } else {
        throw new Exception("Query failed: " . $connection->error);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>