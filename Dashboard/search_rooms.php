<?php
include 'connection.php';

// Initialize response array
$response = array();

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Log the incoming request
    error_log("Room Search Request: " . print_r($_POST, true));

    // Build query
    $query = "SELECT 
                r.*,
                h.name as hostel_name,
                c.name as campus_name,
                COUNT(DISTINCT a.id) as total_applications,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                SUM(CASE WHEN a.status = 'paid' THEN 1 ELSE 0 END) as paid_applications,
                SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) as approved_applications,
                GROUP_CONCAT(DISTINCT CONCAT(i.names, ' (', i.regnumber, ')') SEPARATOR ', ') as occupants
             FROM rooms r
             LEFT JOIN hostels h ON r.hostel_id = h.id
             LEFT JOIN campuses c ON h.campus_id = c.id
             LEFT JOIN applications a ON a.room_id = r.id
             LEFT JOIN info i ON a.regnumber = i.regnumber
             WHERE 1=1";

    // Add search conditions
    if (!empty($_POST['roomNumber'])) {
        $roomNumber = $connection->real_escape_string($_POST['roomNumber']);
        $query .= " AND r.room_code LIKE '%$roomNumber%'";
    }

    if (!empty($_POST['roomHostel'])) {
        $hostel = $connection->real_escape_string($_POST['roomHostel']);
        $query .= " AND h.name = '$hostel'";
    }

    if (!empty($_POST['roomCapacity'])) {
        $capacity = $connection->real_escape_string($_POST['roomCapacity']);
        $query .= " AND r.number_of_beds = '$capacity'";
    }

    if (!empty($_POST['roomAvailability'])) {
        $availability = $connection->real_escape_string($_POST['roomAvailability']);
        if ($availability === 'available') {
            $query .= " AND r.remain > 0";
        } else if ($availability === 'full') {
            $query .= " AND r.remain = 0";
        }
    }

    $query .= " GROUP BY r.id, r.room_code, h.name, c.name ORDER BY h.name, r.room_code";

    // Log the final query
    error_log("Room Search Query: " . $query);

    $result = $connection->query($query);
    
    if ($result) {
        $html = '<div class="table-responsive"><table class="table table-striped">';
        $html .= '<thead><tr>';
        $html .= '<th>Room Code</th>';
        $html .= '<th>Hostel</th>';
        $html .= '<th>Campus</th>';
        $html .= '<th>Capacity</th>';
        $html .= '<th>Available Beds</th>';
        $html .= '<th>Occupancy Rate</th>';
        $html .= '<th>Total Applications</th>';
        $html .= '<th>Pending</th>';
        $html .= '<th>Paid</th>';
        $html .= '<th>Approved</th>';
        $html .= '<th>Occupants</th>';
        $html .= '<th>Status</th>';
        $html .= '</tr></thead><tbody>';

        $data = array();
        while ($row = $result->fetch_assoc()) {
            $occupied_beds = $row['number_of_beds'] - $row['remain'];
            $occupancy_rate = $row['number_of_beds'] > 0 ? 
                ($occupied_beds / $row['number_of_beds']) * 100 : 0;

            $status = '';
            $statusClass = '';
            if ($row['remain'] == 0) {
                $status = 'Full';
                $statusClass = 'text-danger';
            } else if ($row['remain'] == $row['number_of_beds']) {
                $status = 'Empty';
                $statusClass = 'text-success';
            } else {
                $status = 'Partially Occupied';
                $statusClass = 'text-warning';
            }

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['room_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['hostel_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['campus_name']) . '</td>';
            $html .= '<td>' . $row['number_of_beds'] . '</td>';
            $html .= '<td>' . $row['remain'] . '</td>';
            $html .= '<td>' . number_format($occupancy_rate, 1) . '%</td>';
            $html .= '<td>' . $row['total_applications'] . '</td>';
            $html .= '<td>' . $row['pending_applications'] . '</td>';
            $html .= '<td>' . $row['paid_applications'] . '</td>';
            $html .= '<td>' . $row['approved_applications'] . '</td>';
            $html .= '<td>' . ($row['occupants'] ? htmlspecialchars($row['occupants']) : 'No occupants') . '</td>';
            $html .= '<td class="' . $statusClass . '">' . $status . '</td>';
            $html .= '</tr>';

            // Prepare data for Excel export
            $data[] = array(
                'room_code' => $row['room_code'],
                'hostel_name' => $row['hostel_name'],
                'campus_name' => $row['campus_name'],
                'capacity' => $row['number_of_beds'],
                'available_beds' => $row['remain'],
                'occupancy_rate' => number_format($occupancy_rate, 1) . '%',
                'total_applications' => $row['total_applications'],
                'pending_applications' => $row['pending_applications'],
                'paid_applications' => $row['paid_applications'],
                'approved_applications' => $row['approved_applications'],
                'occupants' => $row['occupants'] ?: 'No occupants',
                'status' => $status
            );
        }

        $html .= '</tbody></table></div>';

        $response['success'] = true;
        $response['html'] = $html;
        $response['data'] = $data;
    } else {
        throw new Exception("Query failed: " . $connection->error);
    }

} catch (Exception $e) {
    error_log("Room Search Exception: " . $e->getMessage());
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 