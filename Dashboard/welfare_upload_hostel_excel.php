<?php
// Prevent any output before JSON response
ob_start();

// Increase memory limit and execution time for large files
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutes

// Include files
include('connection.php');
include('./includes/auth.php');

// Check user role
if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated',
        'data' => ['errors' => ['Authentication required']]
    ]);
    exit;
}   

// Check user role
$session_id = $_SESSION['id'];
$roleQuery = "SELECT role FROM users WHERE id = '$session_id'";
$roleResult = $connection->query($roleQuery);

if (!$roleResult || $roleResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'User role not found',
        'data' => ['errors' => ['Invalid user role']]
    ]);
    exit;
}

$userRole = $roleResult->fetch_assoc()['role'];
if ($userRole !== 'warefare' && $userRole !== 'information_modifier') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access',
        'data' => ['errors' => ['Insufficient permissions']]
    ]);
    exit;
}

// Clear any output buffers
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

try {
    // Get JSON data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
        sendJsonResponse('error', 'Invalid or empty data received');
    }

    // Get the header row (first row) to determine column indexes
    $headers = array_map('strtolower', $data['data'][0]);

    // Required columns
    $required_columns = [
        'campus',
        'building code',
        'hostel name',
        'room code',
        'number of beds',
        'gender',
        'year'
    ];

    // Validate all required columns exist
    $missing_columns = [];
    foreach ($required_columns as $column) {
        if (!in_array($column, $headers)) {
            $missing_columns[] = $column;
        }
    }

    if (!empty($missing_columns)) {
        sendJsonResponse('error', 'Missing required columns: ' . implode(', ', $missing_columns));
    }

    // Find indexes based on column names
    $campusIndex = array_search('campus', $headers);
    $buildingCodeIndex = array_search('building code', $headers);
    $hostelNameIndex = array_search('hostel name', $headers);
    $roomCodeIndex = array_search('room code', $headers);
    $bedsIndex = array_search('number of beds', $headers);
    $otherNamesIndex = array_search('other names', $headers);
    $genderIndex = array_search('gender', $headers);
    $yearIndex = array_search('year', $headers);

    // Get user's assigned campus (only for welfare users)
    $userCampus = null;
    if ($userRole === 'warefare') {
        $campusQuery = "SELECT c.name FROM campuses c 
                       INNER JOIN users u ON u.campus = c.id 
                       WHERE u.id = '$session_id'";
        $campusResult = $connection->query($campusQuery);
        
        if (!$campusResult || $campusResult->num_rows === 0) {
            sendJsonResponse('error', 'User is not associated with any campus');
        }
        
        $userCampus = strtolower(trim($campusResult->fetch_assoc()['name']));
    }

    // Skip the header row and process data rows
    $dataRows = array_slice($data['data'], 1);
    $validationErrors = [];

    // First validate all records before any insertion
    foreach ($dataRows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 2;
        
        // Skip empty rows
        $isEmptyRow = true;
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                $isEmptyRow = false;
                break;
            }
        }
        if ($isEmptyRow) continue;

        // Validate required fields
        if (empty($row[$campusIndex]) || empty($row[$buildingCodeIndex]) || 
            empty($row[$hostelNameIndex]) || empty($row[$roomCodeIndex]) || 
            empty($row[$bedsIndex]) || empty($row[$genderIndex]) || 
            empty($row[$yearIndex])) {
            $validationErrors[] = "Row $rowNumber: Missing required fields";
            continue;
        }

        $campusInput = strtolower(trim($connection->real_escape_string($row[$campusIndex])));
        $buildingCode = trim($connection->real_escape_string($row[$buildingCodeIndex]));
        $hostelName = trim($connection->real_escape_string($row[$hostelNameIndex]));
        $gender = trim($connection->real_escape_string($row[$genderIndex]));
        $year = trim($connection->real_escape_string($row[$yearIndex]));

        // Validate gender
        if (!in_array($gender, ['M', 'F'])) {
            $validationErrors[] = "Row $rowNumber: Gender must be either 'M' or 'F' (capital letters)";
            continue;
        }

        // Validate year
        if (!is_numeric($year) || $year < 1 || $year > 6) {
            $validationErrors[] = "Row $rowNumber: Year must be a valid year of study (1-6)";
            continue;
        }

        // Validate campus
        $campusResult = $connection->query("SELECT id FROM campuses WHERE LOWER(TRIM(name)) = '$campusInput'");
        if (!$campusResult || $campusResult->num_rows === 0) {
            $validationErrors[] = "Row $rowNumber: Campus '$campusInput' does not exist";
            continue;
        }
        $campusId = $campusResult->fetch_assoc()['id'];

        // Check if hostel exists with the same name in the same campus
        $hostelNameCheck = $connection->prepare("SELECT COUNT(*) as count FROM hostels WHERE name = ? AND campus_id = ?");
        $hostelNameCheck->bind_param("si", $hostelName, $campusId);
        $hostelNameCheck->execute();
        $hostelNameCount = $hostelNameCheck->get_result()->fetch_assoc()['count'];
        
        if ($hostelNameCount > 0) {
            $validationErrors[] = "Row $rowNumber: Hostel name '$hostelName' already exists in campus '$campusInput'";
            continue;
        }

        // Check if hostel exists with the same building code in the same campus
        $buildingCodeCheck = $connection->prepare("SELECT COUNT(*) as count FROM hostels WHERE building_code = ? AND campus_id = ?");
        $buildingCodeCheck->bind_param("si", $buildingCode, $campusId);
        $buildingCodeCheck->execute();
        $buildingCodeCount = $buildingCodeCheck->get_result()->fetch_assoc()['count'];
        
        if ($buildingCodeCount > 0) {
            $validationErrors[] = "Row $rowNumber: Building code '$buildingCode' already exists in campus '$campusInput'";
            continue;
        }
    }

    // If there are any errors, return them without processing any records
    if (!empty($validationErrors)) {
        sendJsonResponse('error', 'File contains errors. No records were added. Please fix the following errors:', [
            'errors' => $validationErrors
        ]);
    }

    // If we get here, all records are valid. Process them.
    $processedHostels = 0;
    $processedRooms = 0;
    $successMessages = [];
    $roomSuccessMessages = [];
    $hostelMap = [];

    // Group rows by unique hostel (campus, hostel name)
    foreach ($dataRows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 2;
        // Skip empty rows
        $isEmptyRow = true;
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                $isEmptyRow = false;
                break;
            }
        }
        if ($isEmptyRow) continue;

        $campusInput = strtolower(trim($connection->real_escape_string($row[$campusIndex])));
        $buildingCode = trim($connection->real_escape_string($row[$buildingCodeIndex]));
        $hostelName = trim($connection->real_escape_string($row[$hostelNameIndex]));
        $otherNames = isset($row[$otherNamesIndex]) ? trim($connection->real_escape_string($row[$otherNamesIndex])) : '';
        $gender = trim($connection->real_escape_string($row[$genderIndex]));
        $year = trim($connection->real_escape_string($row[$yearIndex]));
        $roomCode = trim($connection->real_escape_string($row[$roomCodeIndex]));
        $numberOfBeds = trim($connection->real_escape_string($row[$bedsIndex]));

        // Get campus id
        $campusResult = $connection->query("SELECT id FROM campuses WHERE LOWER(TRIM(name)) = '$campusInput'");
        $campusId = $campusResult->fetch_assoc()['id'];

        $hostelKey = $campusId . '|' . $hostelName;
        if (!isset($hostelMap[$hostelKey])) {
            $hostelMap[$hostelKey] = [
                'campus_id' => $campusId,
                'name' => $hostelName,
                'building_code' => $buildingCode,
                'othernames' => $otherNames,
                'gender' => $gender,
                'year' => $year,
                'rooms' => []
            ];
        }
        $hostelMap[$hostelKey]['rooms'][] = [
            'room_code' => $roomCode,
            'number_of_beds' => $numberOfBeds,
            'row_number' => $rowNumber
        ];
    }

    // Prepare hostel insert statement
    $hostelSql = "INSERT INTO hostels (name, building_code, othernames, gender, year, campus_id, createdBy, updatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $hostelStmt = $connection->prepare($hostelSql);
    // Prepare room insert statement
    $roomSql = "INSERT INTO rooms (room_code, number_of_beds, hostel_id, remain, status, createdBy, updatedBy, createdAt, updatedAt) VALUES (?, ?, ?, ?, 'published', ?, ?, NOW(), NOW())";
    $roomStmt = $connection->prepare($roomSql);

    foreach ($hostelMap as $hostelKey => $hostelData) {
        // Insert hostel
        if (!$hostelStmt->bind_param("sssssiii",
            $hostelData['name'],
            $hostelData['building_code'],
            $hostelData['othernames'],
            $hostelData['gender'],
            $hostelData['year'],
            $hostelData['campus_id'],
            $session_id,
            $session_id
        )) {
            sendJsonResponse('error', 'Failed to bind parameters for hostel insertion');
        }
        if (!$hostelStmt->execute()) {
            sendJsonResponse('error', 'Failed to execute hostel insertion: ' . $hostelStmt->error);
        }
        $hostelId = $connection->insert_id;
        $processedHostels++;
        $successMessages[] = "Hostel '{$hostelData['name']}' added to campus ID {$hostelData['campus_id']}";

        // Insert rooms for this hostel
        foreach ($hostelData['rooms'] as $room) {
            $remain = is_numeric($room['number_of_beds']) ? intval($room['number_of_beds']) : 0;
            if (!$roomStmt->bind_param("siiiii",
                $room['room_code'],
                $room['number_of_beds'],
                $hostelId,
                $remain,
                $session_id,
                $session_id
            )) {
                sendJsonResponse('error', 'Failed to bind parameters for room insertion');
            }
            if (!$roomStmt->execute()) {
                sendJsonResponse('error', 'Failed to execute room insertion: ' . $roomStmt->error . ' (Room: ' . $room['room_code'] . ' in Hostel: ' . $hostelData['name'] . ')');
            }
            $processedRooms++;
            $roomSuccessMessages[] = "Room '{$room['room_code']}' added to hostel '{$hostelData['name']}' (Row {$room['row_number']})";
        }
    }

    // All records processed successfully
    sendJsonResponse('success', "Successfully processed $processedHostels hostels and $processedRooms rooms", [
        'success' => array_merge($successMessages, $roomSuccessMessages)
    ]);

} catch (Exception $e) {
    sendJsonResponse('error', 'An error occurred: ' . $e->getMessage());
}
?>
