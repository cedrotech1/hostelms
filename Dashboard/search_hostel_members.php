<?php
include 'connection.php';

$response = array();

try {
    $where = " WHERE 1=1";
    if (!empty($_POST['campus'])) {
        $campus = $connection->real_escape_string($_POST['campus']);
        $where .= " AND c.name = '$campus'";
    }
    if (!empty($_POST['hostel'])) {
        $hostel = $connection->real_escape_string($_POST['hostel']);
        $where .= " AND h.name = '$hostel'";
    }

    $query = "SELECT 
                h.name AS hostel_name,
                r.room_code,
                i.regnumber,
                i.names,
                i.campus,
                i.college,
                i.school,
                i.yearofstudy,
                i.phone,
                i.gender
            FROM hostels h
            JOIN rooms r ON r.hostel_id = h.id
            LEFT JOIN applications a ON a.room_id = r.id
            LEFT JOIN info i ON a.regnumber = i.regnumber
            LEFT JOIN campuses c ON h.campus_id = c.id
            $where
            ORDER BY h.name, r.room_code, i.names";

    $result = $connection->query($query);

    if ($result) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            // Only show rows with an applicant
            if ($row['names'] && $row['regnumber']) {
                $rows[] = $row;
            }
        }

        // Group by hostel and room for rowspan logic
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['hostel_name'] . '||' . $row['room_code'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $row;
        }

        $html = '<div class="table-responsive"><table class="table table-striped">';
        $html .= '<thead><tr>';
        $html .= '<th>Reg Number</th><th>Name</th><th>Hostel</th><th>Room</th><th>Campus</th><th>College</th><th>School</th><th>Year of Study</th><th>Phone</th><th>Gender</th>';
        $html .= '</tr></thead><tbody>';

        $data = [];
        foreach ($grouped as $key => $members) {
            $rowspan = count($members);
            foreach ($members as $idx => $row) {
                $html .= '<tr>';
                if ($idx === 0) {
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['regnumber']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['names']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['hostel_name']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['room_code']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['campus']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['college']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['school']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['yearofstudy']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['phone']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['gender']) . '</td>';
                }
                $html .= '</tr>';
                $data[] = [
                    'regnumber' => $row['regnumber'],
                    'names' => $row['names'],
                    'hostel_name' => $row['hostel_name'],
                    'room_code' => $row['room_code'],
                    'campus' => $row['campus'],
                    'college' => $row['college'],
                    'school' => $row['school'],
                    'yearofstudy' => $row['yearofstudy'],
                    'phone' => $row['phone'],
                    'gender' => $row['gender']
                ];
            }
        }

        $html .= '</tbody></table></div>';

        $response['success'] = true;
        $response['html'] = $html;
        $response['data'] = $data;
    } else {
        throw new Exception("Query failed: " . $connection->error);
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?> 