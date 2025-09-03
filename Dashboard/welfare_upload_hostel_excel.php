<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Increase memory limit and execution time for large files
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutes

// Function to send JSON response with proper headers
function sendJsonResponse($status, $message, $data = []) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    http_response_code($status === 'error' ? 400 : 200);
    
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Include database connection
include('connection.php');

// Verify database connection
if ($connection->connect_error) {
    sendJsonResponse('error', 'Database connection failed: ' . $connection->connect_error);
}

// Check user authentication and role
if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    sendJsonResponse('error', 'User not authenticated', ['errors' => ['Authentication required']]);
}

// Validate session_id as an integer
$session_id = filter_var($_SESSION['id'], FILTER_VALIDATE_INT);
if ($session_id === false) {
    sendJsonResponse('error', 'Invalid session ID', ['errors' => ['Session ID must be a valid integer']]);
}

$userRole = $_SESSION['role'];

$roleQuery = "SELECT role FROM users WHERE id = ?";
$stmt = $connection->prepare($roleQuery);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$roleResult = $stmt->get_result();
$stmt->close();

if (!$roleResult || $roleResult->num_rows === 0) {
    sendJsonResponse('error', 'User role not found', ['errors' => ['Invalid user role']]);
}



// Get user's assigned campus (only for welfare users)
$userCampus = null;
if ($userRole === 'warefare' || $userRole === 'wadden') {
    $campusQuery = $connection->prepare("SELECT c.name FROM campuses c INNER JOIN users u ON u.campus = c.id WHERE u.id = ?");
    $campusQuery->bind_param("i", $session_id);
    $campusQuery->execute();
    $campusResult = $campusQuery->get_result();
    $campusQuery->close();
    
    if (!$campusResult || $campusResult->num_rows === 0) {
        sendJsonResponse('error', 'User is not associated with any campus');
    }
    
    $userCampus = strtoupper(trim($campusResult->fetch_assoc()['name']));
}

// Get JSON data
$input = file_get_contents("php://input");
if ($input === false) {
    sendJsonResponse('error', 'Failed to read input data');
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse('error', 'Invalid JSON data: ' . json_last_error_msg());
}

if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
    sendJsonResponse('error', 'Invalid or empty data received');
}

// Log the received data for debugging
error_log('Received data: ' . print_r($data['data'], true));

// Get the header row (first row) to determine column indexes
$headers = array_map('strtolower', $data['data'][0]);

// Required columns
$required_columns = [
    'campus',
    'hostel name',
    'hostel block name',
    'building/hostel block code',
    'room code 1',
    'number of beds',
    'gender',
    'year'
];

// Optional columns
$optional_columns = ['room code 2'];

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
$blockNameIndex = array_search('hostel block name', $headers);
$buildingCodeIndex = array_search('building/hostel block code', $headers);
$roomCode1Index = array_search('room code 1', $headers);
$roomCode2Index = array_search('room code 2', $headers);
$bedsIndex = array_search('number of beds', $headers);
$genderIndex = array_search('gender', $headers);
$yearIndex = array_search('year', $headers);

// Validate data rows
$dataRows = array_slice($data['data'], 1);
$validationErrors = [];
$hostelsData = [];

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
    if ($isEmptyRow) {
        $validationErrors[] = "Row $rowNumber: Skipped empty row";
        continue;
    }

    // Validate required fields
    if (empty($row[$campusIndex]) || empty($row[$hostelNameIndex]) || 
        empty($row[$blockNameIndex]) || empty($row[$buildingCodeIndex]) || 
        empty($row[$roomCode1Index]) || empty($row[$bedsIndex]) || 
        empty($row[$genderIndex]) || empty($row[$yearIndex])) {
        $validationErrors[] = "Row $rowNumber: Missing required fields";
        continue;
    }

    // Convert inputs to uppercase and escape
    $campusInput = strtoupper(trim($connection->real_escape_string($row[$campusIndex])));
    $hostelName = strtoupper(trim($connection->real_escape_string($row[$hostelNameIndex])));
    $blockName = strtoupper(trim($connection->real_escape_string($row[$blockNameIndex])));
    $buildingCode = strtoupper(trim($connection->real_escape_string($row[$buildingCodeIndex])));
    $roomCode1 = strtoupper(trim($connection->real_escape_string($row[$roomCode1Index])));
    $roomCode2 = isset($row[$roomCode2Index]) ? strtoupper(trim($connection->real_escape_string($row[$roomCode2Index]))) : '';
    $gender = strtoupper(trim($connection->real_escape_string($row[$genderIndex])));
    $year = trim($connection->real_escape_string($row[$yearIndex]));
    
    // Combine hostel name and block name with hyphen
    $combinedHostelName = $hostelName . (!empty($blockName) ? '-' . $blockName : '');

    // Validate gender
    if (!in_array($gender, ['M', 'F', 'ALL'])) {
        $validationErrors[] = "Row $rowNumber: Gender must be either 'M', 'F', or 'ALL'";
        continue;
    }

    // Validate year - can be single year or comma-separated list of years
    // $years = array_map('trim', explode(',', $year));
    // $invalidYears = [];
    // foreach ($years as $y) {
    //     if (!is_numeric($y) || $y < 1 || $y > 6) {
    //         $invalidYears[] = $y;
    //     }
    // }
    // if (!empty($invalidYears)) {
    //     $validationErrors[] = "Row $rowNumber: Year must be a valid year of study (1-6) or comma-separated list of years, found invalid: " . implode(', ', $invalidYears);
    //     continue;
    // }

    // Validate campus for welfare users
    // campus should be small case
   // Normalize values for safe comparison
$campusInput = strtolower($campusInput);
$userCampus  = strtolower($userCampus);

// Restrict only wadden and warefare to their assigned campus
if (in_array($userRole, ['wadden', 'warefare']) && $campusInput !== $userCampus) {
    $validationErrors[] = "Row $rowNumber: Unauthorized campus '$campusInput' for this user";
    continue;
}

    // Validate campus exists
    $campusQuery = $connection->prepare("SELECT id FROM campuses WHERE UPPER(name) = ?");
    $campusQuery->bind_param("s", $campusInput);
    $campusQuery->execute();
    $campusResult = $campusQuery->get_result();
    $campusQuery->close();

    if ($campusResult->num_rows === 0) {
        $validationErrors[] = "Row $rowNumber: Campus '$campusInput' does not exist";
        continue;
    }
    $campusId = $campusResult->fetch_assoc()['id'];

    // Check if hostel exists with the same combined name in the same campus
    $hostelNameCheck = $connection->prepare("SELECT id, name FROM hostels WHERE UPPER(name) = ? AND campus_id = ?");
    $hostelNameCheck->bind_param("si", $combinedHostelName, $campusId);
    $hostelNameCheck->execute();
    $hostelNameResult = $hostelNameCheck->get_result();
    $hostelNameCheck->close();
    
    $hostelId = null;
    if ($hostelNameResult->num_rows > 0) {
        $hostel = $hostelNameResult->fetch_assoc();
        $hostelId = $hostel['id'];
        // Instead of skipping, use existing hostel ID
        $validationErrors[] = "Row $rowNumber: Using existing hostel '$combinedHostelName' (ID: $hostelId) for room insertion";
    }

    // Create a unique key for this hostel (based on hostel name and block only)
    $hostelKey = $campusId . '|' . $hostelName . '|' . $blockName;
    
    // Initialize hostel data if not exists
    if (!isset($hostelsData[$hostelKey])) {
        $hostelsData[$hostelKey] = [
            'campus' => $campusInput,
            'campus_id' => $campusId,
            'hostel_name' => $hostelName,
            'block_name' => $blockName,
            'combined_name' => $combinedHostelName,
            'building_code' => $buildingCode,
            'gender' => $gender,
            'year' => $year,
            'hostel_id' => $hostelId, // Store existing hostel ID if found
            'rooms' => []
        ];
    }
    
    // Add room to this hostel
    $hostelsData[$hostelKey]['rooms'][] = [
        'room_code1' => $roomCode1,
        'room_code2' => $roomCode2,
        'number_of_beds' => intval($row[$bedsIndex] ?? 0),
        'row_number' => $rowNumber  // Track which row this room came from
    ];
}

// Process valid data
$successCount = 0;
$createdHostels = [];
$errorMessages = $validationErrors; // Include validation errors in final response

$connection->begin_transaction();

try {
    foreach ($hostelsData as $hostelKey => $hostelData) {
        $campus = $hostelData['campus'];
        $campusId = $hostelData['campus_id'];
        $hostelName = $hostelData['hostel_name'];
        $blockName = $hostelData['block_name'];
        $combinedName = $hostelData['combined_name'];
        $buildingCode = $hostelData['building_code'];
        $gender = $hostelData['gender'];
        // if gender = ALL seve ''
        if($gender == 'ALL') {
            $gender = '';
        }
        $year = $hostelData['year'];
        $rooms = $hostelData['rooms'];
        $hostelId = $hostelData['hostel_id'];
        
        // Insert new hostel if it doesn't exist
        if (!$hostelId) {
            $hostelSql = "INSERT INTO hostels (name, building_code, othernames, gender, year, campus_id, createdBy, updatedBy) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $hostelStmt = $connection->prepare($hostelSql);
            $hostelStmt->bind_param("sssssiii", 
                $combinedName, 
                $buildingCode,
                $blockName,
                $gender,
                $year,
                $campusId,
                $session_id,
                $session_id
            );
            
            if (!$hostelStmt->execute()) {
                $errorMessages[] = "Error creating hostel '$combinedName': " . $connection->error;
                continue; // Skip to next hostel
            }
            
            $hostelId = $connection->insert_id;
            $createdHostels[] = "Created hostel: $combinedName (ID: $hostelId)";
            $successCount++;
            $hostelStmt->close();
        } else {
            $createdHostels[] = "Using existing hostel: $combinedName (ID: $hostelId)";
        }
        
        // Process rooms for this hostel
        $processedRooms = []; // Track processed room codes in this batch
        foreach ($rooms as $room) {
            $roomCode = strtoupper($room['room_code1']);
            
            // Skip if we've already processed this room code in this batch
            if (in_array($roomCode, $processedRooms)) {
                $errorMessages[] = "Row {$room['row_number']}: Skipping duplicate room '$roomCode' in batch for hostel '$combinedName' (ID: $hostelId)";
                continue;
            }
            
            // Check if room exists in database
            $roomCheck = $connection->prepare("SELECT id FROM rooms WHERE UPPER(room_code) = ? AND hostel_id = ?");
            $roomCheck->bind_param("si", $room['room_code1'], $hostelId);
            $roomCheck->execute();
            $roomCheckResult = $roomCheck->get_result();
            $roomExists = $roomCheckResult->num_rows > 0;
            $roomCheck->close();
            
            if ($roomExists) {
                $errorMessages[] = "Row {$room['row_number']}: Room '$roomCode' already exists in hostel '$combinedName' (ID: $hostelId)";
                continue;
            }
            
            // Mark this room code as processed
            $processedRooms[] = $roomCode;
            
            // Insert new room
            $roomSql = "INSERT INTO rooms (room_code, room_code2, number_of_beds, hostel_id, remain, status, createdBy, updatedBy, createdAt, updatedAt) 
                       VALUES (?, ?, ?, ?, ?, 'published', ?, ?, NOW(), NOW())";
            $roomStmt = $connection->prepare($roomSql);
            $roomStmt->bind_param("ssiiisi", 
                $room['room_code1'],
                $room['room_code2'],
                $room['number_of_beds'],
                $hostelId,
                $room['number_of_beds'],
                $session_id,
                $session_id
            );
            
            if (!$roomStmt->execute()) {
                $errorMessages[] = "Error creating room '{$room['room_code1']}' in hostel '$combinedName': " . $connection->error;
                continue;
            }
            
            $successCount++;
            $roomStmt->close();
        }
    }
    
    // Commit transaction
    $connection->commit();
    
    // Prepare success message
    $totalHostels = count($hostelsData);
    $successMessage = "Successfully processed $successCount record" . ($successCount !== 1 ? 's' : '') . " for $totalHostels hostel" . ($totalHostels !== 1 ? 's' : '') . ".";
    if (!empty($createdHostels)) {
        $successMessage .= "\n\n" . implode("\n", $createdHostels);
    }
    
    sendJsonResponse('success', $successMessage, [
        'success' => $successCount,
        'errors' => $errorMessages,
        'hostels_processed' => $createdHostels
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $connection->rollback();
    
    // Log error
    error_log("Error in welfare_upload_hostel_excel.php: " . $e->getMessage());
    
    sendJsonResponse('error', 'An error occurred while processing the file: ' . $e->getMessage(), [
        'success' => $successCount,
        'errors' => array_merge($errorMessages, ["Critical error: " . $e->getMessage()])
    ]);
}

// Close database connection
$connection->close();
?>