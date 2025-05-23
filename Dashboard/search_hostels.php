<?php
include 'connection.php';

// Initialize response array
$response = array();

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Log the incoming request
    error_log("Hostel Search Request: " . print_r($_POST, true));

    // Build query
    $query = "SELECT 
                h.*,
                c.name as campus_name,
                COUNT(DISTINCT r.id) as total_rooms,
                SUM(r.number_of_beds) as total_beds,
                SUM(r.number_of_beds) - COUNT(a.id) as available_beds,
                COUNT(DISTINCT a.id) as total_applications,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                SUM(CASE WHEN a.status = 'paid' THEN 1 ELSE 0 END) as paid_applications,
                SUM(CASE WHEN a.slep = 1 THEN 1 ELSE 0 END) as slep_applications
             FROM hostels h
             LEFT JOIN campuses c ON h.campus_id = c.id
             LEFT JOIN rooms r ON r.hostel_id = h.id
             LEFT JOIN applications a ON a.room_id = r.id
             WHERE 1=1";

    // Add search conditions
    if (!empty($_POST['hostelName'])) {
        $hostelName = $connection->real_escape_string($_POST['hostelName']);
        $query .= " AND h.name LIKE '%$hostelName%'";
    }

    if (!empty($_POST['hostelCampus'])) {
        $campus = $connection->real_escape_string($_POST['hostelCampus']);
        $query .= " AND c.name = '$campus'";
    }

    if (!empty($_POST['roomStatus'])) {
        $status = $connection->real_escape_string($_POST['roomStatus']);
        if ($status === 'available') {
            $query .= " AND r.remain > 0";
        } else if ($status === 'occupied') {
            $query .= " AND r.remain = 0";
        }
    }

    $query .= " GROUP BY h.id, h.name, c.name ORDER BY h.name";

    // Log the final query
    error_log("Hostel Search Query: " . $query);

    $result = $connection->query($query);
    
    if ($result) {
        $html = '<div class="table-responsive"><table class="table table-striped">';
        $html .= '<thead><tr>';
        $html .= '<th>Hostel Name</th>';
        $html .= '<th>Campus</th>';
        $html .= '<th>Total Rooms</th>';
        $html .= '<th>Total Beds</th>';
        $html .= '<th>Available Beds</th>';
        $html .= '<th>Occupancy Rate</th>';
        $html .= '<th>Total Applications</th>';
        $html .= '<th>Pending</th>';
        $html .= '<th>Paid</th>';
        $html .= '<th>SLEP</th>';
        $html .= '</tr></thead><tbody>';

        $data = array();
        while ($row = $result->fetch_assoc()) {
            $occupied_beds = $row['total_beds'] - $row['available_beds'];
            $occupancy_rate = $row['total_beds'] > 0 ? 
                ($occupied_beds / $row['total_beds']) * 100 : 0;

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['campus_name']) . '</td>';
            $html .= '<td>' . $row['total_rooms'] . '</td>';
            $html .= '<td>' . $row['total_beds'] . '</td>';
            $html .= '<td>' . $row['available_beds'] . '</td>';
            $html .= '<td>' . number_format($occupancy_rate, 1) . '%</td>';
            $html .= '<td>' . $row['total_applications'] . '</td>';
            $html .= '<td>' . $row['pending_applications'] . '</td>';
            $html .= '<td>' . $row['paid_applications'] . '</td>';
            $html .= '<td>' . $row['slep_applications'] . '</td>';
            $html .= '</tr>';

            // Prepare data for Excel export
            $data[] = array(
                'name' => $row['name'],
                'campus_name' => $row['campus_name'],
                'total_rooms' => $row['total_rooms'],
                'total_beds' => $row['total_beds'],
                'available_beds' => $row['available_beds'],
                'occupancy_rate' => number_format($occupancy_rate, 1) . '%',
                'total_applications' => $row['total_applications'],
                'pending_applications' => $row['pending_applications'],
                'paid_applications' => $row['paid_applications'],
                'slep_applications' => $row['slep_applications']
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
    error_log("Hostel Search Exception: " . $e->getMessage());
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 