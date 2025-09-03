<?php
include('connection.php');

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Check database connection
if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$campus = isset($_GET['campus']) ? $_GET['campus'] : '';

// Build query
$where = "u.role != 'information_modifier'";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (u.names LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}
if ($role) {
    $where .= " AND u.role = ?";
    $params[] = $role;
    $types .= 's';
}
if ($status !== '') {
    $where .= " AND u.active = ?";
    $params[] = $status;
    $types .= 'i';
}
if ($campus) {
    $where .= " AND u.campus = ?";
    $params[] = $campus;
    $types .= 'i';
}

// Total count
$totalQuery = "SELECT COUNT(*) as total FROM users u WHERE $where";
$stmt = $connection->prepare($totalQuery);
if (!$stmt) {
    error_log("Total query prepare failed: " . $connection->error);
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed']);
    exit;
}
if ($params) {
    $stmt->bind_param($types, ...$params);
}
if (!$stmt->execute()) {
    error_log("Total query execution failed: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed']);
    exit;
}
$totalResult = $stmt->get_result();
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Fetch users
$query = "SELECT u.id, u.names, u.email, u.phone, u.role, u.active, u.campus, u.image, c.name as campus_name, c.id as campus_id 
          FROM users u 
          LEFT JOIN campuses c ON u.campus = c.id 
          WHERE $where 
          LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $connection->prepare($query);
if (!$stmt) {
    error_log("Users query prepare failed: " . $connection->error);
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed']);
    exit;
}
$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    error_log("Users query execution failed: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed']);
    exit;
}
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $row['campus_name'] = $row['campus_name'] ? $row['campus_name'] : 'N/A';
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['users' => $users, 'totalPages' => $totalPages, 'page' => $page]);
?>