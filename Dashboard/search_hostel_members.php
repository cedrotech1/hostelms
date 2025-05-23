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
                i.names AS applicant_name,
                i.regnumber
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
            if ($row['applicant_name'] && $row['regnumber']) {
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
        $html .= '<thead><tr><th>Hostel</th><th>Room</th><th>Applicant Name</th><th>Reg Number</th></tr></thead><tbody>';

        $data = [];
        foreach ($grouped as $key => $members) {
            $rowspan = count($members);
            foreach ($members as $idx => $row) {
                $html .= '<tr>';
                if ($idx === 0) {
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['hostel_name']) . '</td>';
                    $html .= '<td rowspan="' . $rowspan . '">' . htmlspecialchars($row['room_code']) . '</td>';
                }
                $html .= '<td>' . htmlspecialchars($row['applicant_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['regnumber']) . '</td>';
                $html .= '</tr>';
                $data[] = [
                    'hostel_name' => $row['hostel_name'],
                    'room_code' => $row['room_code'],
                    'applicant_name' => $row['applicant_name'],
                    'regnumber' => $row['regnumber']
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