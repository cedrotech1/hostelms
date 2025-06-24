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

    // Debug: Log the headers being detected
    error_log("Detected headers: " . implode(', ', $headers));

    // Required columns for student data
    $required_columns = [
        'regnumber',
        'campus',
        'college',
        'sirname',
        'lastname',
        'school',
        'program',
        'intake',
        'disability',
        'yearofstudy',
        'email',
        'gender',
        'nid',
        'phone'
    ];

    // Validate all required columns exist
    $missing_columns = [];
    foreach ($required_columns as $column) {
        if (!in_array($column, $headers)) {
            $missing_columns[] = $column;
        }
    }

    if (!empty($missing_columns)) {
        sendJsonResponse('error', 'Missing required columns: ' . implode(', ', $missing_columns) . '. Detected headers: ' . implode(', ', $headers));
    }

    // Find indexes based on column names
    $regnumberIndex = array_search('regnumber', $headers);
    $campusIndex = array_search('campus', $headers);
    $collegeIndex = array_search('college', $headers);
    $sirnameIndex = array_search('sirname', $headers);
    $lastnameIndex = array_search('lastname', $headers);
    $schoolIndex = array_search('school', $headers);
    $programIndex = array_search('program', $headers);
    $intakeIndex = array_search('intake', $headers);
    $disabilityIndex = array_search('disability', $headers);
    $yearofstudyIndex = array_search('yearofstudy', $headers);
    $emailIndex = array_search('email', $headers);
    $genderIndex = array_search('gender', $headers);
    $nidIndex = array_search('nid', $headers);
    $phoneIndex = array_search('phone', $headers);

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
        $missingFields = [];
        if (!isset($row[$regnumberIndex]) || trim($row[$regnumberIndex]) === '') $missingFields[] = 'regnumber';
        if (!isset($row[$campusIndex]) || trim($row[$campusIndex]) === '') $missingFields[] = 'campus';
        if (!isset($row[$collegeIndex]) || trim($row[$collegeIndex]) === '') $missingFields[] = 'college';
        if (!isset($row[$sirnameIndex]) || trim($row[$sirnameIndex]) === '') $missingFields[] = 'sirname';
        if (!isset($row[$lastnameIndex]) || trim($row[$lastnameIndex]) === '') $missingFields[] = 'lastname';
        if (!isset($row[$schoolIndex]) || trim($row[$schoolIndex]) === '') $missingFields[] = 'school';
        if (!isset($row[$programIndex]) || trim($row[$programIndex]) === '') $missingFields[] = 'program';
        if (!isset($row[$intakeIndex]) || trim($row[$intakeIndex]) === '') $missingFields[] = 'intake';
        if (!isset($row[$disabilityIndex]) || trim($row[$disabilityIndex]) === '') $missingFields[] = 'disability';
        if (!isset($row[$yearofstudyIndex]) || trim($row[$yearofstudyIndex]) === '') $missingFields[] = 'yearofstudy';
        if (!isset($row[$emailIndex]) || trim($row[$emailIndex]) === '') $missingFields[] = 'email';
        if (!isset($row[$genderIndex]) || trim($row[$genderIndex]) === '') $missingFields[] = 'gender';
        if (!isset($row[$nidIndex]) || trim($row[$nidIndex]) === '') $missingFields[] = 'nid';
        if (!isset($row[$phoneIndex]) || trim($row[$phoneIndex]) === '') $missingFields[] = 'phone';
        
        if (!empty($missingFields)) {
            $validationErrors[] = "Row $rowNumber: Missing required fields: " . implode(', ', $missingFields);
            continue;
        }

        $regnumber = trim($connection->real_escape_string($row[$regnumberIndex]));
        $campusInput = strtolower(trim($connection->real_escape_string($row[$campusIndex])));
        $college = trim($connection->real_escape_string($row[$collegeIndex]));
        $sirname = trim($connection->real_escape_string($row[$sirnameIndex]));
        $lastname = trim($connection->real_escape_string($row[$lastnameIndex]));
        $school = trim($connection->real_escape_string($row[$schoolIndex]));
        $program = trim($connection->real_escape_string($row[$programIndex]));
        $intake = trim($connection->real_escape_string($row[$intakeIndex]));
        $disability = trim($connection->real_escape_string($row[$disabilityIndex]));
        $yearofstudy = trim($connection->real_escape_string($row[$yearofstudyIndex]));
        $email = trim($connection->real_escape_string($row[$emailIndex]));
        $genderRaw = strtolower(trim($connection->real_escape_string($row[$genderIndex])));
        $nid = trim($connection->real_escape_string($row[$nidIndex]));
        $phone = preg_replace('/\D/', '', $row[$phoneIndex]); // keep digits only

        // Debug: Log the disability value for row 2
        if ($rowNumber == 2) {
            error_log("Row 2 disability value: '" . $disability . "' (length: " . strlen($disability) . ")");
            error_log("Row 2 disability index: " . $disabilityIndex);
            error_log("Row 2 raw data: " . implode(', ', $row));
        }

        // Validate registration number uniqueness
        $regCheck = $connection->prepare("SELECT COUNT(*) as count FROM info WHERE regnumber = ?");
        $regCheck->bind_param("s", $regnumber);
        $regCheck->execute();
        $regCount = $regCheck->get_result()->fetch_assoc()['count'];
        
        if ($regCount > 0) {
            $validationErrors[] = "Row $rowNumber: Registration number '$regnumber' already exists";
            continue;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = "Row $rowNumber: Invalid email format '$email'";
            continue;
        }

        // Validate email uniqueness
        $emailCheck = $connection->prepare("SELECT COUNT(*) as count FROM info WHERE email = ?");
        $emailCheck->bind_param("s", $email);
        $emailCheck->execute();
        $emailCount = $emailCheck->get_result()->fetch_assoc()['count'];
        
        if ($emailCount > 0) {
            $validationErrors[] = "Row $rowNumber: Email '$email' already exists";
            continue;
        }

        // Validate and normalize gender
        $genderMap = [
            'm' => 'M', 'male' => 'M', 'man' => 'M', 'boy' => 'M', 'masculine' => 'M',
            'f' => 'F', 'female' => 'F', 'woman' => 'F', 'girl' => 'F', 'feminine' => 'F'
        ];
        $gender = '';
        if (isset($genderMap[$genderRaw])) {
            $gender = $genderMap[$genderRaw];
        }
        if (empty($gender)) {
            $validationErrors[] = "Row $rowNumber: Invalid gender value '$genderRaw'. Allowed: M, F, male, female, man, woman, boy, girl, masculine, feminine (case-insensitive)";
            continue;
        }

        // Validate year of study
        if (!is_numeric($yearofstudy) || $yearofstudy < 1 || $yearofstudy > 6) {
            $validationErrors[] = "Row $rowNumber: Year of study must be a valid year (1-6)";
            continue;
        }

        // Validate phone number
        if (isset($phone[0]) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }
        if (strlen($phone) < 10) {
            $validationErrors[] = "Row $rowNumber: Invalid phone number '$phone'";
            continue;
        }

        // Validate disability field
        if ($disability !== '0' && $disability !== '1') {
            $validationErrors[] = "Row $rowNumber: Disability must be either '0' (No disability) or '1' (Has disability)";
            continue;
        }

        // Validate campus
        $campusResult = $connection->query("SELECT id FROM campuses WHERE LOWER(TRIM(name)) = '$campusInput'");
        if (!$campusResult || $campusResult->num_rows === 0) {
            $validationErrors[] = "Row $rowNumber: Campus '$campusInput' does not exist";
            continue;
        }

        // For welfare users, validate campus restriction
        if ($userRole === 'warefare' && $userCampus !== $campusInput) {
            $validationErrors[] = "Row $rowNumber: You can only upload data for campus '$userCampus'";
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
    $processedRows = 0;
    $successMessages = [];
    $sql = "INSERT INTO info (regnumber, campus, college, names, school, program, intake, disability, yearofstudy, email, gender, nid, phone, token, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);

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

        $regnumber = trim($connection->real_escape_string($row[$regnumberIndex]));
        $campusInput = strtolower(trim($connection->real_escape_string($row[$campusIndex])));
        $college = trim($connection->real_escape_string($row[$collegeIndex]));
        $sirname = trim($connection->real_escape_string($row[$sirnameIndex]));
        $lastname = trim($connection->real_escape_string($row[$lastnameIndex]));
        $school = trim($connection->real_escape_string($row[$schoolIndex]));
        $program = trim($connection->real_escape_string($row[$programIndex]));
        $intake = trim($connection->real_escape_string($row[$intakeIndex]));
        $disability = trim($connection->real_escape_string($row[$disabilityIndex]));
        $yearofstudy = trim($connection->real_escape_string($row[$yearofstudyIndex]));
        $email = trim($connection->real_escape_string($row[$emailIndex]));
        $genderRaw = strtolower(trim($connection->real_escape_string($row[$genderIndex])));
        $nid = trim($connection->real_escape_string($row[$nidIndex]));
        $phone = preg_replace('/\D/', '', $row[$phoneIndex]);

        // Debug: Log the disability value for row 2
        if ($rowNumber == 2) {
            error_log("Row 2 disability value: '" . $disability . "' (length: " . strlen($disability) . ")");
            error_log("Row 2 disability index: " . $disabilityIndex);
            error_log("Row 2 raw data: " . implode(', ', $row));
        }

        // Normalize gender for insertion (always 'M' or 'F')
        $gender = '';
        if (isset($genderMap[$genderRaw])) {
            $gender = $genderMap[$genderRaw];
        } else {
            $gender = 'F'; // fallback, but should never happen due to validation
        }
        
        // Normalize phone
        if (isset($phone[0]) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }

        // Combine names
        $names = $sirname . ' ' . $lastname;

        // Default values
        $token = '';
        $status = 'active';

        if (!$stmt->bind_param("sssssssssssssss", 
            $regnumber,
            $campusInput,
            $college,
            $names,
            $school,
            $program,
            $intake,
            $disability,
            $yearofstudy,
            $email,
            $gender,
            $nid,
            $phone,
            $token,
            $status
        )) {
            sendJsonResponse('error', 'Failed to bind parameters for student insertion');
        }

        if (!$stmt->execute()) {
            sendJsonResponse('error', 'Failed to execute student insertion: ' . $stmt->error);
        }

        $successMessages[] = "Row $rowNumber: Successfully added student '$names' ($regnumber)";
        $processedRows++;
    }

    // All records processed successfully
    sendJsonResponse('success', "Successfully processed $processedRows student records", [
        'success' => $successMessages
    ]);

} catch (Exception $e) {
    sendJsonResponse('error', 'An error occurred: ' . $e->getMessage());
}
?> 