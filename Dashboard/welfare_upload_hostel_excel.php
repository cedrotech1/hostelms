<?php
include('connection.php');
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Start session and check if user is logged in
// session_start();
// if (!isset($_SESSION['campus'])) {
//     http_response_code(401);
//     echo json_encode(['message' => 'Unauthorized access']);
//     exit;
// }

$userid=$_SESSION['id'];
$ok1 = mysqli_query($connection, "select * from users where id=$userid");
                  while ($row = mysqli_fetch_array($ok1)) {
                    $id = $row["id"];
                
                    $campus = $row["campus"];
                    
                }

$user_campus_id =$campus;

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

    // Verify that the campus name matches the user's allocated campus
    $stmt = $connection->prepare("SELECT id FROM campuses WHERE id = ? AND name = ?");
    $stmt->bind_param("is", $user_campus_id, $campus_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        continue; // Skip if campus name doesn't match user's allocated campus
    }
    $stmt->close();

    // 1. Get or insert campus
    $stmt = $connection->prepare("SELECT id FROM campuses WHERE id = ?");
    $stmt->bind_param("i", $user_campus_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        continue; // Skip if campus doesn't exist
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
        $insert_room = $connection->prepare("INSERT INTO rooms (room_code, number_of_beds, remain, hostel_id) VALUES (?, ?, ?, ?)");
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
