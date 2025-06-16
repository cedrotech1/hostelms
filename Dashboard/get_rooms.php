<?php
include('connection.php');
include('./includes/auth.php');

header('Content-Type: application/json');

try {
    if (!isset($_GET['hostel_id']) || !is_numeric($_GET['hostel_id'])) {
        throw new Exception('Invalid hostel ID');
    }

    $hostel_id = (int)$_GET['hostel_id'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    
    // Verify hostel exists
    $check_hostel = mysqli_prepare($connection, "SELECT id FROM hostels WHERE id = ?");
    mysqli_stmt_bind_param($check_hostel, "i", $hostel_id);
    mysqli_stmt_execute($check_hostel);
    $hostel_result = mysqli_stmt_get_result($check_hostel);
    
    if (mysqli_num_rows($hostel_result) === 0) {
        throw new Exception('Hostel not found');
    }

    // Calculate offset
    $offset = ($page - 1) * $per_page;

    // Build search condition
    $search_condition = "";
    if (!empty($search)) {
        $search_condition = " AND (room_code LIKE ? OR number_of_beds LIKE ?)";
    }

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM rooms WHERE hostel_id = ?" . $search_condition;
    $count_stmt = mysqli_prepare($connection, $count_query);
    
    if (!empty($search)) {
        $search_param = "%$search%";
        mysqli_stmt_bind_param($count_stmt, "iss", $hostel_id, $search_param, $search_param);
    } else {
        mysqli_stmt_bind_param($count_stmt, "i", $hostel_id);
    }
    
    mysqli_stmt_execute($count_stmt);
    $total_result = mysqli_stmt_get_result($count_stmt);
    $total_row = mysqli_fetch_assoc($total_result);
    $total = $total_row['total'];

    // Get rooms for current page
    $query = "SELECT r.*, 
              creator.names as created_by, 
              updater.names as updated_by,
              DATE_FORMAT(r.createdAt, '%Y-%m-%d') as createdAt,
              DATE_FORMAT(r.updatedAt, '%Y-%m-%d') as updatedAt
              FROM rooms r 
              LEFT JOIN users creator ON r.createdBy = creator.id 
              LEFT JOIN users updater ON r.updatedBy = updater.id 
              WHERE r.hostel_id = ?" . $search_condition . " ORDER BY room_code LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($connection, $query);
    
    if (!empty($search)) {
        $search_param = "%$search%";
        mysqli_stmt_bind_param($stmt, "issii", $hostel_id, $search_param, $search_param, $per_page, $offset);
    } else {
        mysqli_stmt_bind_param($stmt, "iii", $hostel_id, $per_page, $offset);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rooms = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = array(
            'id' => $row['id'],
            'room_code' => $row['room_code'],
            'number_of_beds' => $row['number_of_beds'],
            'remain' => $row['remain'],
            'created_by' => $row['created_by'],
            'updated_by' => $row['updated_by'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt']
        );
    }

    // Calculate pagination info
    $total_pages = ceil($total / $per_page);
    $start = $offset + 1;
    $end = min($offset + $per_page, $total);

    echo json_encode(array(
        'rooms' => $rooms,
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'start' => $start,
        'end' => $end
    ));

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
}
?> 