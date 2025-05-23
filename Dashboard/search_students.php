<?php
include 'connection.php';

// Initialize response array
$response = array();

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Log the incoming request
    error_log("Student Search Request: " . print_r($_POST, true));

    // Build query
    $query = "SELECT 
                i.*,
                a.status as application_status,
                a.slep,
                a.created_at as application_date,
                r.room_code,
                h.name as hostel_name,
                c.name as campus_name
             FROM info i
             LEFT JOIN applications a ON i.regnumber = a.regnumber
             LEFT JOIN rooms r ON a.room_id = r.id
             LEFT JOIN hostels h ON r.hostel_id = h.id
             LEFT JOIN campuses c ON h.campus_id = c.id
             WHERE 1=1";

    // Add search conditions
    if (!empty($_POST['regNumber'])) {
        $regNumber = $connection->real_escape_string($_POST['regNumber']);
        $query .= " AND i.regnumber LIKE '%$regNumber%'";
    }

    if (!empty($_POST['studentName'])) {
        $studentName = $connection->real_escape_string($_POST['studentName']);
        $query .= " AND i.names LIKE '%$studentName%'";
    }

    if (!empty($_POST['studentCampus'])) {
        $campus = $connection->real_escape_string($_POST['studentCampus']);
        $query .= " AND i.campus = '$campus'";
    }

    if (!empty($_POST['applicationStatus'])) {
        $status = $connection->real_escape_string($_POST['applicationStatus']);
        $query .= " AND a.status = '$status'";
    }

    if (!empty($_POST['gender'])) {
        $gender = $connection->real_escape_string($_POST['gender']);
        $query .= " AND LOWER(i.gender) = LOWER('$gender')";
    }

    if (!empty($_POST['yearOfStudy'])) {
        $year = $connection->real_escape_string($_POST['yearOfStudy']);
        $query .= " AND i.yearofstudy = '$year'";
    }

    $query .= " ORDER BY i.regnumber";

    // Log the final query
    error_log("Student Search Query: " . $query);

    $result = $connection->query($query);
    
    if ($result) {
        if ($result->num_rows > 0) {
            $html = '<div class="table-responsive"><table class="table table-striped">';
            $html .= '<thead><tr>';
            $html .= '<th>Reg Number</th>';
            $html .= '<th>Name</th>';
            $html .= '<th>Campus</th>';
            $html .= '<th>College</th>';
            $html .= '<th>School</th>';
            $html .= '<th>Program</th>';
            $html .= '<th>Year</th>';
            $html .= '<th>Gender</th>';
            $html .= '<th>Email</th>';
            $html .= '<th>Phone</th>';
            $html .= '<th>Application Status</th>';
            $html .= '<th>Room</th>';
            $html .= '<th>Hostel</th>';
            $html .= '<th>Application Date</th>';
            $html .= '</tr></thead><tbody>';

            $data = array();
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row['regnumber']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['names']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['campus']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['college']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['school']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['program']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['yearofstudy']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['gender']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['email']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['phone']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['application_status']) . ($row['slep'] ? ' (SLEP)' : '') . '</td>';
                $html .= '<td>' . htmlspecialchars($row['room_code'] ?: 'Not Assigned') . '</td>';
                $html .= '<td>' . htmlspecialchars($row['hostel_name'] ?: 'Not Assigned') . '</td>';
                $html .= '<td>' . ($row['application_date'] ? date('Y-m-d', strtotime($row['application_date'])) : 'N/A') . '</td>';
                $html .= '</tr>';

                // Prepare data for Excel export
                $data[] = array(
                    'regnumber' => $row['regnumber'],
                    'names' => $row['names'],
                    'campus' => $row['campus'],
                    'college' => $row['college'],
                    'school' => $row['school'],
                    'program' => $row['program'],
                    'yearofstudy' => $row['yearofstudy'],
                    'gender' => $row['gender'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'application_status' => $row['application_status'] . ($row['slep'] ? ' (SLEP)' : ''),
                    'room_code' => $row['room_code'] ?: 'Not Assigned',
                    'hostel_name' => $row['hostel_name'] ?: 'Not Assigned',
                    'application_date' => $row['application_date'] ? date('Y-m-d', strtotime($row['application_date'])) : 'N/A'
                );
            }

            $html .= '</tbody></table></div>';

            $response['success'] = true;
            $response['html'] = $html;
            $response['data'] = $data;
        } else {
            $response['success'] = true;
            $response['html'] = '<div class="alert alert-info">No students found matching the search criteria.</div>';
            $response['data'] = array();
        }
    } else {
        throw new Exception("Query failed: " . $connection->error);
    }

} catch (Exception $e) {
    error_log("Student Search Exception: " . $e->getMessage());
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Ensure no output before headers
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Send JSON response
echo json_encode($response);
exit;
?> 