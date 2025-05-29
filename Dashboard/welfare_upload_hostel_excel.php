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
$userId = $_SESSION['id'];
$roleQuery = "SELECT role FROM users WHERE id = '$userId'";
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
        'hostel name',
        'room code',
        'number of beds'
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
    $hostelNameIndex = array_search('hostel name', $headers);
    $roomCodeIndex = array_search('room code', $headers);
    $bedsIndex = array_search('number of beds', $headers);

    // Get user's assigned campus (only for welfare users)
    $userCampus = null;
    if ($userRole === 'warefare') {
        $campusQuery = "SELECT c.name FROM campuses c 
                       INNER JOIN users u ON u.campus = c.id 
                       WHERE u.id = '$userId'";
        $campusResult = $connection->query($campusQuery);
        
        if (!$campusResult || $campusResult->num_rows === 0) {
            sendJsonResponse('error', 'User is not associated with any campus');
        }
        
        $userCampus = strtolower(trim($campusResult->fetch_assoc()['name']));
    }

    // Skip the header row and process data rows
    $dataRows = array_slice($data['data'], 1);
    $results = [
        'success' => [],
        'errors' => []
    ];

    // Prepare batch insert
    $batchSize = 100; // Process 100 rows at a time
    $totalRows = count($dataRows);
    $processedRows = 0;
    $batchValues = [];
    $batchErrors = [];

    foreach ($dataRows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 2; // +2 because we skipped header and array is 0-based
        
        // Check if row is empty or contains only whitespace
        $isEmptyRow = true;
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                $isEmptyRow = false;
                break;
            }
        }
        
        if ($isEmptyRow) {
            continue; // Skip empty rows silently
        }

        // Validate required fields
        if (empty($row[$campusIndex]) || empty($row[$hostelNameIndex]) || 
            empty($row[$roomCodeIndex]) || empty($row[$bedsIndex])) {
            $batchErrors[] = "Row $rowNumber: Missing required fields";
            continue;
        }

        $campusInput = strtolower(trim($connection->real_escape_string($row[$campusIndex])));
        $hostelName = trim($connection->real_escape_string($row[$hostelNameIndex]));
        $roomCode = trim($connection->real_escape_string($row[$roomCodeIndex]));
        $numberOfBeds = (int)$row[$bedsIndex];

        // Validate campus matches user's assigned campus (only for welfare users)
        if ($userRole === 'warefare' && $campusInput !== $userCampus) {
            $batchErrors[] = "Row $rowNumber: Campus '$campusInput' does not match your assigned campus '$userCampus'";
            continue;
        }

        // Validate number of beds
        if ($numberOfBeds <= 0) {
            $batchErrors[] = "Row $rowNumber: Number of beds must be greater than 0";
            continue;
        }

        // Check if campus exists
        $campusResult = $connection->query("SELECT id FROM campuses WHERE LOWER(TRIM(name)) = '$campusInput'");
        if (!$campusResult || $campusResult->num_rows === 0) {
            $batchErrors[] = "Row $rowNumber: Campus '$campusInput' does not exist";
            continue;
        }
        $campusId = $campusResult->fetch_assoc()['id'];

        // Check if hostel exists
        $hostelResult = $connection->query("SELECT id FROM hostels WHERE name = '$hostelName' AND campus_id = $campusId");
        if (!$hostelResult || $hostelResult->num_rows === 0) {
            // Create new hostel
            if (!$connection->query("INSERT INTO hostels (name, campus_id) VALUES ('$hostelName', $campusId)")) {
                $batchErrors[] = "Row $rowNumber: Failed to create hostel '$hostelName'";
                continue;
            }
            $hostelId = $connection->insert_id;
        } else {
            $hostelId = $hostelResult->fetch_assoc()['id'];
        }

        // Check if room already exists
        $roomResult = $connection->query("SELECT id FROM rooms WHERE room_code = '$roomCode' AND hostel_id = $hostelId");
        if ($roomResult && $roomResult->num_rows > 0) {
            $batchErrors[] = "Row $rowNumber: Room '$roomCode' already exists in hostel '$hostelName'";
            continue;
        }

        // Insert room
        if (!$connection->query("INSERT INTO rooms (room_code, number_of_beds, remain, hostel_id) 
                               VALUES ('$roomCode', $numberOfBeds, $numberOfBeds, $hostelId)")) {
            $batchErrors[] = "Row $rowNumber: Failed to create room '$roomCode'";
            continue;
        }

        $results['success'][] = "Row $rowNumber: Successfully added room '$roomCode' to hostel '$hostelName'";
        $processedRows++;
    }

    // Send final response
    if (empty($results['errors'])) {
        sendJsonResponse('success', "Successfully processed $processedRows records", $results);
    } else {
        sendJsonResponse('partial', "Processed $processedRows records with some errors", $results);
    }

} catch (Exception $e) {
    sendJsonResponse('error', 'An error occurred: ' . $e->getMessage());
}
?>
