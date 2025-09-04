<?php
// Set content type to JSON
header('Content-Type: application/json');

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send JSON error response
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

try {
    include 'connection.php';

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        sendError('Unauthorized access', 401);
    }

            // Get request parameters with validation
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $campus = isset($_GET['campus']) && !empty($_GET['campus']) ? intval($_GET['campus']) : 0;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    
    // Debug log for incoming parameters
    error_log("Status: " . $status . ", Type: " . gettype($status));
    
    // Initialize parameters arrays
    $countParams = [];
    $mainQueryParams = [];
    
    if ($page < 1) $page = 1;
$perPage = 10; // Number of items per page
$offset = ($page - 1) * $perPage;

// Check if user is HQ
$is_hq = true; // Assuming this page is only accessible to HQ users

// Build the base query
$query = "SELECT u.*, c.name as campus_name, 
          (SELECT GROUP_CONCAT(DISTINCT h2.name ORDER BY h2.name SEPARATOR ', ') 
           FROM wadden_hostels wh2 
           LEFT JOIN hostels h2 ON wh2.hostel_id = h2.id 
           WHERE wh2.wadden_id = u.id) as assigned_hostels
          FROM users u
          LEFT JOIN campuses c ON u.campus = c.id
          WHERE u.role = 'wadden'";

// Add campus filter to main query
if ($campus > 0) {
    $query .= " AND u.campus = ?";
    $mainQueryParams[] = $campus;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total 
               FROM users u 
               WHERE u.role = 'wadden'";

// Add campus filter to count query if specified
if ($campus > 0) {
    $countQuery .= " AND u.campus = ?";
    $countParams[] = $campus;
}

// Add search conditions to count query
if (!empty($search)) {
    $countQuery .= " AND (u.names LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}
if ($status !== '') {
    $countQuery .= " AND u.active = ?";
    $activeStatus = ($status === '1' || $status === 1) ? 1 : 0;
    $countParams[] = $activeStatus;
    error_log("Count Query - Status: " . $activeStatus);
}
if ($campus > 0) {
    $countQuery .= " AND u.campus = ?";
    $countParams[] = $campus;
}

// Add search conditions to main query
if (!empty($search)) {
    $query .= " AND (u.names LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $mainQueryParams[] = $searchTerm;
    $mainQueryParams[] = $searchTerm;
}
if ($status !== '') {
    $query .= " AND u.active = ?";
    $activeStatus = ($status === '1' || $status === 1) ? 1 : 0;
    $mainQueryParams[] = $activeStatus;
    error_log("Main Query - Status: " . $activeStatus);
}
if ($campus > 0) {
    $query .= " AND u.campus = ?";
    $mainQueryParams[] = $campus;
}

$stmt = mysqli_prepare($connection, $countQuery);

    // Bind parameters for count query
    if (!empty($countParams)) {
        $types = str_repeat('s', count($countParams));
        mysqli_stmt_bind_param($stmt, $types, ...$countParams);
    }

if (!mysqli_stmt_execute($stmt)) {
    throw new Exception('Failed to execute count query: ' . mysqli_error($connection));
}

$totalResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRows = $totalRow['total'];
$totalPages = ceil($totalRows / $perPage);

// Add GROUP BY and ORDER BY to the main query
$query .= " GROUP BY u.id, u.names, u.email, u.phone, u.image, u.active, u.campus, c.name ORDER BY u.names ASC LIMIT ? OFFSET ?";

// Prepare the main query
$stmt = mysqli_prepare($connection, $query);
if ($stmt === false) {
    throw new Exception('Failed to prepare query: ' . mysqli_error($connection));
}

// Prepare the parameter types and values
$types = '';
$bindParams = [];

// Add filter parameters
if (!empty($mainQueryParams)) {
    foreach ($mainQueryParams as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } else {
            $types .= 's';
        }
        $bindParams[] = $param;
    }
}

// Add pagination parameters
$types .= 'ii';
$bindParams[] = (int)$perPage;
$bindParams[] = (int)$offset;

// Debug output (you can remove this in production)
error_log("Query: " . $query);
error_log("Types: " . $types);
error_log("Params: " . print_r($bindParams, true));

// Bind all parameters at once
if (!empty($bindParams)) {
    // Create references for bind_param
    $bindParams = array_merge([$stmt, $types], $bindParams);
    $refs = [];
    foreach ($bindParams as $key => $value) {
        $refs[$key] = &$bindParams[$key];
    }
    call_user_func_array('mysqli_stmt_bind_param', $refs);
}

if (!mysqli_stmt_execute($stmt)) {
    throw new Exception('Failed to execute query: ' . mysqli_error($connection));
}

$result = mysqli_stmt_get_result($stmt);

// Fetch results
$waddens = [];
while ($row = mysqli_fetch_assoc($result)) {
    $waddens[] = [
        'id' => $row['id'],
        'names' => $row['names'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'image' => $row['image'] ?: 'assets/img/profile-img.jpg',
        'campus' => $row['campus'],
        'campus_name' => $row['campus_name'],
        'assigned_hostels' => $row['assigned_hostels'],
        'active' => (bool)$row['active']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'waddens' => $waddens,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalItems' => $totalRows
]);

} catch (Exception $e) {
    // Log the error
    error_log('Error in fetch_waddens.php: ' . $e->getMessage());
    
    // Send detailed error in development, generic in production
    $errorMessage = 'An error occurred while processing your request.';
    if (ini_get('display_errors')) {
        $errorMessage .= ' ' . $e->getMessage();
    }
    
    // Send error response
    sendError($errorMessage, 500);
}

// Close connection
// if (isset($connection) && $connection) {
//     mysqli_close($connection);
// }
?>
