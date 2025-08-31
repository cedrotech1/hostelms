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
            'stats' => ['total_rooms' => 0, 'total_beds' => 0, 'available_beds' => 0, 'occupancy_rate' => 0]
        ]);
        exit();
    }
    
    $hostel_ids_str = implode(',', $assigned_hostel_ids);
    
    // Build WHERE clause
    $where = " WHERE h.id IN ($hostel_ids_str)";
    
    if (!empty($_POST['hostel'])) {
        $hostel_id = $connection->real_escape_string($_POST['hostel']);
        $where .= " AND h.id = '$hostel_id'";
    }
    
    if (!empty($_POST['room'])) {
        $room = $connection->real_escape_string($_POST['room']);
        $where .= " AND r.room_code LIKE '%$room%'";
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
                $where .= " AND EXISTS (SELECT 1 FROM applications a WHERE a.room_id = r.id)";
                break;
            case 'no_occupants':
                $where .= " AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.room_id = r.id)";
                break;
        }
    }

    // Get rooms data with occupancy information
    $query = "SELECT 
                h.id AS hostel_id,
                h.name AS hostel_name,
                h.building_code,
                r.id AS room_id,
                r.room_code,
                r.number_of_beds,
                r.remain,
                (r.number_of_beds - r.remain) AS occupied_beds,
                ROUND(((r.number_of_beds - r.remain) / r.number_of_beds) * 100, 1) AS occupancy_rate,
                COUNT(a.id) AS occupant_count,
                GROUP_CONCAT(DISTINCT i.names ORDER BY i.names SEPARATOR ', ') AS occupant_names,
                GROUP_CONCAT(DISTINCT i.regnumber ORDER BY i.regnumber SEPARATOR ', ') AS occupant_regnumbers
            FROM hostels h
            JOIN rooms r ON r.hostel_id = h.id
            LEFT JOIN applications a ON a.room_id = r.id
            LEFT JOIN info i ON a.regnumber = i.regnumber
            $where
            GROUP BY h.id, h.name, h.building_code, r.id, r.room_code, r.number_of_beds, r.remain
            ORDER BY h.name, r.room_code";

    $result = $connection->query($query);

    if ($result) {
        $rows = [];
        $stats = [
            'total_rooms' => 0,
            'total_beds' => 0,
            'available_beds' => 0,
            'occupancy_rate' => 0,
            'full_rooms' => 0,
            'empty_rooms' => 0,
            'partial_rooms' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $stats['total_rooms']++;
            $stats['total_beds'] += $row['number_of_beds'];
            $stats['available_beds'] += $row['remain'];
            
            // Count room types
            if ($row['remain'] == 0) {
                $stats['full_rooms']++;
            } elseif ($row['remain'] == $row['number_of_beds']) {
                $stats['empty_rooms']++;
            } else {
                $stats['partial_rooms']++;
            }
        }
        
        if ($stats['total_beds'] > 0) {
            $stats['occupancy_rate'] = round((($stats['total_beds'] - $stats['available_beds']) / $stats['total_beds']) * 100, 1);
        }

        // Generate HTML
        $html = '';
        if (empty($rows)) {
            $html = '<div class="alert alert-info">No rooms found for the selected criteria.</div>';
        } else {
            // Create a table with all rooms
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';
            $html .= '<thead class="table-dark">';
            $html .= '<tr>';
            $html .= '<th>Hostel</th>';
            $html .= '<th>Room Code</th>';
            $html .= '<th>Capacity</th>';
            $html .= '<th>Occupied</th>';
            $html .= '<th>Available</th>';
            $html .= '<th>Occupancy Rate</th>';
            $html .= '<th>Status</th>';
            $html .= '<th>Occupants</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            
            foreach ($rows as $room) {
                // Determine room status and color
                $status_class = '';
                $status_text = '';
                
                if ($room['remain'] == 0) {
                    $status_class = 'status-full';
                    $status_text = 'Full';
                } elseif ($room['remain'] == $room['number_of_beds']) {
                    $status_class = 'status-empty';
                    $status_text = 'Empty';
                } else {
                    $status_class = 'status-partial';
                    $status_text = 'Partial';
                }
                
                // Determine occupancy rate color
                $occupancy_color = 'text-success';
                if ($room['occupancy_rate'] < 50) {
                    $occupancy_color = 'text-warning';
                } elseif ($room['occupancy_rate'] > 90) {
                    $occupancy_color = 'text-danger';
                }
                
                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars($room['hostel_name']) . '</strong><br><small class="text-muted">(' . htmlspecialchars($room['building_code']) . ')</small></td>';
                $html .= '<td><strong>' . htmlspecialchars($room['room_code']) . '</strong></td>';
                $html .= '<td>' . $room['number_of_beds'] . ' beds</td>';
                $html .= '<td class="text-success">' . $room['occupied_beds'] . '</td>';
                $html .= '<td class="text-primary">' . $room['remain'] . '</td>';
                $html .= '<td class="' . $occupancy_color . '"><strong>' . $room['occupancy_rate'] . '%</strong></td>';
                $html .= '<td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>';
                $html .= '<td>';
                if ($room['occupant_count'] > 0) {
                    $html .= '<small><strong>' . $room['occupant_count'] . ' occupant(s)</strong></small><br>';
                    $html .= '<small class="text-muted">' . htmlspecialchars($room['occupant_names']) . '</small>';
                } else {
                    $html .= '<span class="text-muted">No occupants</span>';
                }
                $html .= '</td>';
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
            'stats' => $stats
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