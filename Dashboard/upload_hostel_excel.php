<?php
include('connection.php');
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['data']) || !is_array($data['data'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid input data']);
    exit;
}

$rows = $data['data'];
$inserted = 0;

foreach ($rows as $row) {
    $campus_name     = trim($row[0] ?? '');
    $hostelname      = trim($row[1] ?? '');
    $room_code       = trim($row[2] ?? '');
    $number_of_beds  = (int)($row[3] ?? 0);

    if (!$campus_name || !$hostelname || !$room_code || $number_of_beds <= 0) {
        continue; // Skip invalid rows
    }

    // check if campus name exist in table info column name campus if not not upload hostel
    $stmt = $connection->prepare("SELECT id FROM info WHERE campus = ?");
    $stmt->bind_param("s", $campus_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        continue; // Skip if campus name does not exist
        echo json_encode(['message' => 'Campus name does not exist']);
        exit;
    }



    // 1. Get or insert campus
    $stmt = $connection->prepare("SELECT id FROM campuses WHERE name = ?");
    $stmt->bind_param("s", $campus_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $insert_campus = $connection->prepare("INSERT INTO campuses (name) VALUES (?)");
        $insert_campus->bind_param("s", $campus_name);
        $insert_campus->execute();
        $campus_id = $insert_campus->insert_id;
        $insert_campus->close();
    } else {
        $stmt->bind_result($campus_id);
        $stmt->fetch();
        $stmt->close();
    }

    // 2. Get or insert hostel
    $stmt = $connection->prepare("SELECT id FROM hostels WHERE name = ? AND campus_id = ?");
    $stmt->bind_param("si", $hostelname, $campus_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $insert_hostel = $connection->prepare("INSERT INTO hostels (name, campus_id) VALUES (?, ?)");
        $insert_hostel->bind_param("si", $hostelname, $campus_id);
        $insert_hostel->execute();
        $hostel_id = $insert_hostel->insert_id;
        $insert_hostel->close();
    } else {
        $stmt->bind_result($hostel_id);
        $stmt->fetch();
        $stmt->close();
    }

    // 3. Check for existing room
    $stmt = $connection->prepare("SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ?");
    $stmt->bind_param("si", $room_code, $hostel_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $insert_room = $connection->prepare("INSERT INTO rooms (room_code, number_of_beds,remain, hostel_id) VALUES (?, ?, ?, ?)");
        $insert_room->bind_param("siii", $room_code, $number_of_beds, $number_of_beds, $hostel_id);
        $insert_room->execute();
        $insert_room->close();
        $inserted++;
    } else {
        $stmt->close(); // Room already exists, skip insertion
    }
}

if ($inserted > 0) {
    echo json_encode(['message' => "$inserted rows inserted successfully"]);
} else {
    echo json_encode(['message' => 'No new rows were inserted (duplicates or invalid data)']);
}
?>
