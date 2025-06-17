<?php
// Start output buffering
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set error handler to catch all errors
function handleError($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $errstr
    ]);
    exit;
}
set_error_handler('handleError');

// Set exception handler
function handleException($e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
set_exception_handler('handleException');

include('connection.php');
include('./includes/auth.php');
// session_start();
$id =$_SESSION['id'];
$sql = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($connection, $sql);
$row = mysqli_fetch_assoc($result);
$mycampus = $row['campus'];
$role=$row['role'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    // Clear any previous output
    ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    try {
        // Log the incoming request data
        error_log('Request data: ' . print_r($_REQUEST, true));
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_hostel':
                // Validate required fields
                $hostel_id = $_POST['id'] ?? $_GET['id'] ?? null;
                if (empty($hostel_id)) {
                    throw new Exception('Hostel ID is required');
                }

                // Get hostel data
                $stmt = $connection->prepare("
                    SELECT h.*, c.name as campus_name, 
                           u1.names as created_by, u2.names as updated_by
                    FROM hostels h
                    LEFT JOIN campuses c ON h.campus_id = c.id
                    LEFT JOIN users u1 ON h.createdBy = u1.id
                    LEFT JOIN users u2 ON h.updatedBy = u2.id
                    WHERE h.id = ?
                ");
                $stmt->bind_param("i", $hostel_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $hostel = $result->fetch_assoc();

                if ($hostel) {
                    echo json_encode([
                        'success' => true,
                        'data' => $hostel
                    ]);
                } else {
                    throw new Exception('Hostel not found');
                }
                break;

            case 'add_hostel':
                // Validate required fields
                if (empty($_POST['name']) || empty($_POST['building_code']) || empty($_POST['campus_id']) || empty($_POST['gender']) || empty($_POST['year'])) {
                    throw new Exception('Name, Building Code, Campus, Gender, and Year are required fields');
                }

                // Check if hostel name already exists in the campus
                $stmt = $connection->prepare("SELECT id FROM hostels WHERE name = ? AND campus_id = ?");
                $stmt->bind_param("si", $_POST['name'], $_POST['campus_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A hostel with this name already exists in this campus');
                }

                // Check if building code already exists in the campus
                $stmt = $connection->prepare("SELECT id FROM hostels WHERE building_code = ? AND campus_id = ?");
                $stmt->bind_param("si", $_POST['building_code'], $_POST['campus_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A hostel with this building code already exists in this campus');
                }

                // Store values in variables
                $name = $_POST['name'];
                $building_code = $_POST['building_code'];
                $othernames = $_POST['othernames'] ?? '';
                $gender = $_POST['gender'];
                $year = $_POST['year'];
                $intake = $_POST['intake'] ?? null;
                $campus_id = $_POST['campus_id'];
                $status = 'draft';
                $createdBy = $_SESSION['id'];
                $updatedBy = $_SESSION['id'];

                // Insert new hostel with user tracking
                $stmt = $connection->prepare("INSERT INTO hostels (name, building_code, othernames, gender, year, intake, campus_id, status, createdBy, updatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssii", 
                    $name,
                    $building_code,
                    $othernames,
                    $gender,
                    $year,
                    $intake,
                    $campus_id,
                    $status,
                    $createdBy,
                    $updatedBy
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Hostel added successfully'
                    ]);
                } else {
                    throw new Exception('Failed to add hostel: ' . $stmt->error);
                }
                break;

            case 'edit_hostel':
                // Validate required fields
                if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['building_code']) || empty($_POST['campus_id'])) {
                    throw new Exception('ID, Name, Building Code, and Campus are required fields');
                }

                // Store values in variables
                $id = $_POST['id'];
                $name = $_POST['name'];
                $building_code = $_POST['building_code'];
                $othernames = $_POST['othernames'] ?? '';
                $gender = $_POST['gender'];
                $year = $_POST['year'];
                $intake = $_POST['intake'] ?? null;
                $campus_id = $_POST['campus_id'];
                $college = $_POST['college'] ?? '';
                $school = $_POST['school'] ?? '';
                $disability = $_POST['disability'] ?? '';
                $status = $_POST['status'] ?? 'draft';
                $updatedBy = $_SESSION['id'];

                // Check if hostel name already exists in the campus (excluding current hostel)
                $stmt = $connection->prepare("SELECT id FROM hostels WHERE name = ? AND campus_id = ? AND id != ?");
                $stmt->bind_param("sii", $name, $campus_id, $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A hostel with this name already exists in this campus');
                }

                // Check if building code already exists in the campus (excluding current hostel)
                $stmt = $connection->prepare("SELECT id FROM hostels WHERE building_code = ? AND campus_id = ? AND id != ?");
                $stmt->bind_param("sii", $building_code, $campus_id, $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A hostel with this building code already exists in this campus');
                }

                // Update hostel with user tracking
                $stmt = $connection->prepare("UPDATE hostels SET name = ?, building_code = ?, othernames = ?, gender = ?, year = ?, intake = ?, campus_id = ?, college = ?, school = ?, disability = ?, status = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sssssssssisii", 
                    $name,
                    $building_code,
                    $othernames,
                    $gender,
                    $year,
                    $intake,
                    $campus_id,
                    $college,
                    $school,
                    $disability,
                    $status,
                    $updatedBy,
                    $id
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Hostel updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update hostel: ' . $stmt->error);
                }
                break;

            case 'add_room':
                // Validate required fields
                if (empty($_POST['hostel_id']) || empty($_POST['room_code']) || empty($_POST['number_of_beds'])) {
                    throw new Exception('Hostel ID, Room Code, and Number of Beds are required fields');
                }

                // Check if room code already exists in the hostel
                $stmt = $connection->prepare("SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ?");
                $stmt->bind_param("si", $_POST['room_code'], $_POST['hostel_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A room with this code already exists in this hostel');
                }

                // Store values in variables
                $hostel_id = $_POST['hostel_id'];
                $room_code = $_POST['room_code'];
                $number_of_beds = $_POST['number_of_beds'];
                $remain = $number_of_beds; // Set remain equal to number_of_beds initially
                $createdBy = $_SESSION['id'];
                $updatedBy = $_SESSION['id'];

                // Insert new room
                $stmt = $connection->prepare("INSERT INTO rooms (hostel_id, room_code, number_of_beds, remain, createdBy, updatedBy) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiii", 
                    $hostel_id,
                    $room_code,
                    $number_of_beds,
                    $remain,
                    $createdBy,
                    $updatedBy
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Room added successfully'
                    ]);
                } else {
                    throw new Exception('Failed to add room: ' . $stmt->error);
                }
                break;

            case 'edit_room':
                // Validate required fields
                if (empty($_POST['room_id']) || empty($_POST['hostel_id']) || empty($_POST['room_code']) || empty($_POST['number_of_beds'])) {
                    throw new Exception('Room ID, Hostel ID, Room Code, and Number of Beds are required fields');
                }

                // Check if room code already exists in the hostel (excluding current room)
                $stmt = $connection->prepare("SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ? AND id != ?");
                $stmt->bind_param("sii", $_POST['room_code'], $_POST['hostel_id'], $_POST['room_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A room with this code already exists in this hostel');
                }

                // Get current room data
                $stmt = $connection->prepare("SELECT number_of_beds, remain FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $_POST['room_id']);
                $stmt->execute();
                $current_room = $stmt->get_result()->fetch_assoc();

                if (!$current_room) {
                    throw new Exception('Room not found');
                }

                // Calculate new remain value
                $old_beds = $current_room['number_of_beds'];
                $old_remain = $current_room['remain'];
                $new_beds = $_POST['number_of_beds'];
                $bed_difference = $new_beds - $old_beds;
                $new_remain = $old_remain + $bed_difference;

                // Ensure remain doesn't go below 0
                if ($new_remain < 0) {
                    throw new Exception('Cannot reduce number of beds below current occupancy');
                }

                // Store values in variables
                $room_id = $_POST['room_id'];
                $hostel_id = $_POST['hostel_id'];
                $room_code = $_POST['room_code'];
                $number_of_beds = $new_beds;
                $updatedBy = $_SESSION['id'];

                // Update room
                $stmt = $connection->prepare("UPDATE rooms SET room_code = ?, number_of_beds = ?, remain = ?, updatedBy = ? WHERE id = ? AND hostel_id = ?");
                $stmt->bind_param("siiiii", 
                    $room_code,
                    $number_of_beds,
                    $new_remain,
                    $updatedBy,
                    $room_id,
                    $hostel_id
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Room updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update room: ' . $stmt->error);
                }
                break;

            case 'delete_room':
                // Validate required fields
                if (empty($_POST['room_id'])) {
                    throw new Exception('Room ID is required');
                }

                // Store values in variables
                $room_id = $_POST['room_id'];

                // Delete room
                $stmt = $connection->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Room deleted successfully'
                    ]);
                } else {
                    throw new Exception('Failed to delete room: ' . $stmt->error);
                }
                break;

            case 'update_room_status':
                // Validate required fields
                if (empty($_POST['room_id']) || empty($_POST['status'])) {
                    throw new Exception('Room ID and Status are required fields');
                }

                // Validate status value
                $valid_statuses = ['draft', 'published', 'reserved'];
                if (!in_array($_POST['status'], $valid_statuses)) {
                    throw new Exception('Invalid status value');
                }

                // Store values in variables
                $room_id = $_POST['room_id'];
                $status = $_POST['status'];
                $updatedBy = $_SESSION['id'];

                // Update room status
                $stmt = $connection->prepare("UPDATE rooms SET status = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sii", 
                    $status,
                    $updatedBy,
                    $room_id
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Room status updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update room status: ' . $stmt->error);
                }
                break;

            case 'delete_hostel':
                // Validate required fields
                if (empty($_POST['id'])) {
                    throw new Exception('Hostel ID is required');
                }

                // Store values in variables
                $hostel_id = $_POST['id'];

                // First delete all rooms in the hostel
                $stmt = $connection->prepare("DELETE FROM rooms WHERE hostel_id = ?");
                $stmt->bind_param("i", $hostel_id);
                $stmt->execute();

                // Then delete the hostel
                $stmt = $connection->prepare("DELETE FROM hostels WHERE id = ?");
                $stmt->bind_param("i", $hostel_id);

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Hostel and its rooms deleted successfully'
                    ]);
                } else {
                    throw new Exception('Failed to delete hostel: ' . $stmt->error);
                }
                break;

            case 'add_campus':
                // Validate required fields
                if (empty($_POST['campus_name'])) {
                    throw new Exception('Campus name is required');
                }

                // Check if campus name already exists
                $stmt = $connection->prepare("SELECT id FROM campuses WHERE name = ?");
                $stmt->bind_param("s", $_POST['campus_name']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A campus with this name already exists');
                }

                // Store values in variables
                $name = $_POST['campus_name'];
                $createdBy = $_SESSION['id'];
                $updatedBy = $_SESSION['id'];

                // Insert new campus
                $stmt = $connection->prepare("INSERT INTO campuses (name, createdBy, updatedBy) VALUES (?, ?, ?)");
                $stmt->bind_param("sii", 
                    $name,
                    $createdBy,
                    $updatedBy
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Campus added successfully'
                    ]);
                } else {
                    throw new Exception('Failed to add campus: ' . $stmt->error);
                }
                break;

            case 'edit_campus':
                // Validate required fields
                if (empty($_POST['campus_id']) || empty($_POST['campus_name'])) {
                    throw new Exception('Campus ID and name are required');
                }

                // Check if campus name already exists (excluding current campus)
                $stmt = $connection->prepare("SELECT id FROM campuses WHERE name = ? AND id != ?");
                $stmt->bind_param("si", $_POST['campus_name'], $_POST['campus_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A campus with this name already exists');
                }

                // Store values in variables
                $id = $_POST['campus_id'];
                $name = $_POST['campus_name'];
                $updatedBy = $_SESSION['id'];

                // Update campus
                $stmt = $connection->prepare("UPDATE campuses SET name = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sii", 
                    $name,
                    $updatedBy,
                    $id
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Campus updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update campus: ' . $stmt->error);
                }
                break;

            case 'delete_campus':
                // Validate required fields
                if (empty($_POST['campus_id'])) {
                    throw new Exception('Campus ID is required');
                }

                // Store values in variables
                $campus_id = $_POST['campus_id'];

                // First delete all hostels and their rooms in the campus
                $stmt = $connection->prepare("
                    DELETE r FROM rooms r 
                    INNER JOIN hostels h ON r.hostel_id = h.id 
                    WHERE h.campus_id = ?
                ");
                $stmt->bind_param("i", $campus_id);
                $stmt->execute();

                // Then delete all hostels in the campus
                $stmt = $connection->prepare("DELETE FROM hostels WHERE campus_id = ?");
                $stmt->bind_param("i", $campus_id);
                $stmt->execute();

                // Finally delete the campus
                $stmt = $connection->prepare("DELETE FROM campuses WHERE id = ?");
                $stmt->bind_param("i", $campus_id);

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Campus and all associated data deleted successfully'
                    ]);
                } else {
                    throw new Exception('Failed to delete campus: ' . $stmt->error);
                }
                break;

            case 'update_hostel_status':
                // Validate required fields
                if (empty($_POST['hostel_id']) || empty($_POST['status'])) {
                    throw new Exception('Hostel ID and Status are required fields');
                }

                // Validate status value
                $valid_statuses = ['draft', 'published'];
                if (!in_array($_POST['status'], $valid_statuses)) {
                    throw new Exception('Invalid status value');
                }

                // Store values in variables
                $hostel_id = $_POST['hostel_id'];
                $status = $_POST['status'];
                $updatedBy = $_SESSION['id'];

                // Update hostel status
                $stmt = $connection->prepare("UPDATE hostels SET status = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sii", 
                    $status,
                    $updatedBy,
                    $hostel_id
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Hostel status updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update hostel status: ' . $stmt->error);
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        // Clear any output and set error response
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
if($role === 'warefare'){       
    // Get campuses for warefare role - only their assigned campus
    $campuses_query = mysqli_query($connection, "SELECT * FROM campuses WHERE id = $mycampus ORDER BY name");
} else {
    // Get all campuses for other roles
    $campuses_query = mysqli_query($connection, "SELECT * FROM campuses ORDER BY name");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hostels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">
    
    <!-- Include your existing CSS files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Manage Hostels</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Hostel Management</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Campus Section -->
                    <a href="add_hostel.php"><button class="btn btn-primary mb-3"> <span class="bi bi-upload"></span> upload excel file</button></a> 
                    <div class="card">
                        <div class="card-body">
                        
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                                <h5 class="card-title">
                                    <i class="bi bi-building me-2"></i>Campuses
                                </h5>
                                <?php if($role !== 'warefare'): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCampusModal">
                                    <i class="bi bi-plus-circle me-1"></i>Add Campus
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($campus = mysqli_fetch_assoc($campuses_query)): 
                                            // Get creator and updater names
                                            $creator_name = 'Unknown';
                                            $updater_name = 'Unknown';
                                            
                                            if (!empty($campus['createdBy'])) {
                                                $creator_query = mysqli_query($connection, "SELECT names FROM users WHERE id = " . intval($campus['createdBy']));
                                                if ($creator = mysqli_fetch_assoc($creator_query)) {
                                                    $creator_name = $creator['names'];
                                                }
                                            }
                                            
                                            if (!empty($campus['updatedBy'])) {
                                                $updater_query = mysqli_query($connection, "SELECT names FROM users WHERE id = " . intval($campus['updatedBy']));
                                                if ($updater = mysqli_fetch_assoc($updater_query)) {
                                                    $updater_name = $updater['names'];
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $campus['id']; ?></td>
                                            <td><?php echo htmlspecialchars($campus['name']); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details" onclick="viewCampusDetails(<?php echo $campus['id']; ?>, '<?php echo htmlspecialchars($campus['name']); ?>', '<?php echo htmlspecialchars($creator_name); ?>', '<?php echo !empty($campus['createdAt']) ? date('Y-m-d H:i:s', strtotime($campus['createdAt'])) : '-'; ?>', '<?php echo htmlspecialchars($updater_name); ?>', '<?php echo !empty($campus['updatedAt']) ? date('Y-m-d H:i:s', strtotime($campus['updatedAt'])) : '-'; ?>')">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Campus" onclick="editCampus(<?php echo $campus['id']; ?>, '<?php echo htmlspecialchars($campus['name']); ?>')">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <?php if($role !== 'warefare'): ?>
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Campus" onclick="deleteCampus(<?php echo $campus['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View Hostels" onclick="showHostels(<?php echo $campus['id']; ?>)">
                                                        <i class="bi bi-building"></i> View Hostels
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Hostels Section (Initially Hidden) -->
                    <div id="hostelsSection" class="card mt-4" style="display: none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                                <h5 class="card-title">
                                    <i class="bi bi-house-door me-2"></i>Hostels
                                </h5>
                                <button type="button" class="btn btn-primary" onclick="showAddHostelModal()">
                                    <i class="bi bi-plus-circle me-1"></i>Add Hostel
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Building Code</th>
                                            <!-- <th>Other Names</th> -->
                                            <th>Gender</th>
                                            <th>Year</th>
                                            
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hostelsTableBody">
                                        <!-- Hostels will be loaded here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Rooms Section (Initially Hidden) -->
                    <div id="roomsSection" class="card mt-4" style="display: none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                                <h5 class="card-title">
                                    <i class="bi bi-door-open me-2"></i>Rooms 
                                </h5>
                                <div class="d-flex gap-2">
                                    <div class="input-group" style="width: 300px;">
                                        <input type="text" id="roomSearch" class="form-control" placeholder="Search rooms...">
                                        <button class="btn btn-outline-secondary" type="button" onclick="searchRooms()">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="showAddRoomModal()">
                                        <i class="bi bi-plus-circle me-1"></i>Add Room
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Room Code</th>
                                            <th>Number of Beds</th>
                                            <th>Available Beds</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="roomsTableBody">
                                        <!-- Rooms will be loaded here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Campus Modal -->
    <div class="modal fade" id="addCampusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-building me-2"></i>Add Campus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_campus">
                        <input type="hidden" name="campus_id" value="">
                        <div class="mb-3">
                            <label class="form-label">Campus Name</label>
                            <input type="text" class="form-control" name="campus_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Save Campus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hostel Modal -->
    <div class="modal fade" id="hostelModal" tabindex="-1" aria-labelledby="hostelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hostelModalLabel">Add Hostel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="hostelForm">
                        <input type="hidden" id="hostel_id" name="hostel_id">
                        <input type="hidden" id="campus_id" name="campus_id">
                        <input type="hidden" id="action" name="action" value="add_hostel">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Hostel Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="building_code" class="form-label">Building Code *</label>
                            <input type="text" class="form-control" id="building_code" name="building_code" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="othernames" class="form-label">Other Names</label>
                            <input type="text" class="form-control" id="othernames" name="othernames">
                        </div>
                        
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender *</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="year" class="form-label">Year *</label>
                            <div class="border rounded p-2">
                                <div class="d-flex flex-wrap gap-2" id="yearOptions">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo '<div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="year[]" value="' . $i . '" id="year' . $i . '">
                                            <label class="form-check-label" for="year' . $i . '">Year ' . $i . '</label>
                                        </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <input type="hidden" id="year" name="year">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <!-- Fields for editing only -->
                        <div id="editFields" style="display: none;">
                            <div class="mb-3">
                                <label for="intake" class="form-label">Intake</label>
                                <div class="border rounded p-2">
                                    <div class="d-flex flex-wrap gap-2" id="intakeOptions">
                                        <?php
                                        $intakes_query = mysqli_query($connection, "SELECT DISTINCT intake FROM info WHERE intake IS NOT NULL AND intake != '' ORDER BY intake");
                                        while ($intake = mysqli_fetch_assoc($intakes_query)) {
                                            echo '<div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="intake[]" value="' . htmlspecialchars($intake['intake']) . '" id="intake_' . md5($intake['intake']) . '">
                                                <label class="form-check-label" for="intake_' . md5($intake['intake']) . '">' . htmlspecialchars($intake['intake']) . '</label>
                                            </div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <input type="hidden" id="intake" name="intake">
                            </div>

                            <div class="mb-3">
                                <label for="college" class="form-label">College</label>
                                <div class="border rounded p-2">
                                    <div class="d-flex flex-wrap gap-2" id="collegeOptions">
                                        <?php
                                        $colleges_query = mysqli_query($connection, "SELECT DISTINCT college FROM info WHERE college IS NOT NULL AND college != '' ORDER BY college");
                                        while ($college = mysqli_fetch_assoc($colleges_query)) {
                                            echo '<div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="college[]" value="' . htmlspecialchars($college['college']) . '" id="college_' . md5($college['college']) . '">
                                                <label class="form-check-label" for="college_' . md5($college['college']) . '">' . htmlspecialchars($college['college']) . '</label>
                                            </div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <input type="hidden" id="college" name="college">
                            </div>

                            <div class="mb-3">
                                <label for="school" class="form-label">School</label>
                                <div class="border rounded p-2">
                                    <div class="d-flex flex-wrap gap-2" id="schoolOptions">
                                        <?php
                                        $schools_query = mysqli_query($connection, "SELECT DISTINCT school FROM info WHERE school IS NOT NULL AND school != '' ORDER BY school");
                                        while ($school = mysqli_fetch_assoc($schools_query)) {
                                            echo '<div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="school[]" value="' . htmlspecialchars($school['school']) . '" id="school_' . md5($school['school']) . '">
                                                <label class="form-check-label" for="school_' . md5($school['school']) . '">' . htmlspecialchars($school['school']) . '</label>
                                            </div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <input type="hidden" id="school" name="school">
                            </div>

                            <div class="mb-3">
                                <label for="disability" class="form-label">Disability</label>
                                <select class="form-select" id="disability" name="disability">
                                    <option value="">Select Disability</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveHostel()">Save Hostel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">Add Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <input type="hidden" id="room_id" name="room_id">
                        <input type="hidden" id="hostel_id" name="hostel_id">
                        
                        <div class="mb-3">
                            <label for="room_code" class="form-label">Room Code</label>
                            <input type="text" class="form-control" id="room_code" name="room_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="number_of_beds" class="form-label">Number of Beds</label>
                            <input type="number" class="form-control" id="number_of_beds" name="number_of_beds" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveRoom()">Save Room</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-group .btn {
            margin: 0 2px;
        }
        .table th {
            font-weight: 600;
        }
        .modal-header {
            border-radius: 0.3rem 0.3rem 0 0;
        }
        .card {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: none;
            border-radius: 0.5rem;
        }
        .card-title {
            color: #012970;
            font-weight: 600;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .btn-primary {
            background-color: #4154f1;
            border-color: #4154f1;
        }
        .btn-primary:hover {
            background-color: #3647d4;
            border-color: #3647d4;
        }
        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: #fff;
        }
        .btn-info:hover {
            background-color: #0bb6d9;
            border-color: #0bb6d9;
            color: #fff;
        }
        .pagination {
            margin-bottom: 0;
        }
        .pagination .page-link {
            color:rgb(245, 246, 255);
        }
        .pagination .page-item.active .page-link {
            background-color: #4154f1;
            border-color: #4154f1;
        }
        .pagination .page-link:hover {
            color: #3647d4;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
        }
    </style>

    <!-- Add SweetAlert2 JS before your custom scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentHostelId = null;
        let currentCampusId = null;
        let currentPage = 1;
        let searchQuery = '';
        const roomsPerPage = 10;

        // Function to show consistent alerts
        function showAlert(icon, title, text, callback = null) {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#3085d6'
            }).then((result) => {
                if (callback && result.isConfirmed) {
                    callback();
                }
            });
        }

        // Function to format date and time
        function formatDateTime(dateString) {
            if (!dateString || dateString === '-') return '-';
            try {
                // Parse the MySQL datetime string
                const [datePart, timePart] = dateString.split(' ');
                const [year, month, day] = datePart.split('-');
                const [hours, minutes, seconds] = timePart.split(':');
                
                // Create date object with the exact values from MySQL
                const date = new Date(year, month - 1, day, hours, minutes, seconds);
                
                // Format with 24-hour time to match database format
                return date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                    timeZone: 'UTC' // Use UTC to match database time
                }).replace(',', '');
            } catch (e) {
                console.error('Error formatting date:', e);
                return dateString;
            }
        }

        // Function to load rooms
        function loadRooms() {
            if (!currentHostelId) {
                console.log('No hostel selected');
                const tbody = document.getElementById('roomsTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center">Please select a hostel to view rooms</td>
                        </tr>
                    `;
                }
                return;
            }

            console.log('Loading rooms for hostel:', currentHostelId);
            fetch(`get_rooms.php?hostel_id=${currentHostelId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Received rooms data:', data);
                    const tbody = document.getElementById('roomsTableBody');
                    if (!tbody) {
                        console.error('Rooms table body not found');
                        return;
                    }
                    
                    tbody.innerHTML = '';

                    if (!data.rooms || data.rooms.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center">No rooms found for this hostel</td>
                            </tr>
                        `;
                        return;
                    }

                    data.rooms.forEach(room => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${room.room_code}</td>
                            <td>${room.number_of_beds}</td>
                            <td>${room.remain}</td>
                            <td>${getRoomStatusBadge(room.status)}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details" onclick="viewRoomDetails(${JSON.stringify(room).replace(/"/g, '&quot;')})">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Room" onclick="editRoom(${room.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Room" onclick="deleteRoom(${room.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                ${getRoomStatusButton(room)}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Initialize tooltips after adding all rows
                    initializeTooltips();
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                    const tbody = document.getElementById('roomsTableBody');
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-danger">
                                    Error loading rooms. Please try again.
                                </td>
                            </tr>
                        `;
                    }
                });
        }

        // Helper function to get room status badge
        function getRoomStatusBadge(status) {
            const statusConfig = {
                'draft': { class: 'warning', icon: 'bi-clock', text: 'Draft' },
                'published': { class: 'success', icon: 'bi-globe', text: 'Published' },
                'reserved': { class: 'danger', icon: 'bi-lock', text: 'Reserved' }
            };
            const config = statusConfig[status] || { class: 'secondary', icon: 'bi-question-circle', text: 'Unknown' };
            return `<span class="badge bg-${config.class}"><i class="bi ${config.icon}"></i> ${config.text}</span>`;
        }

        // Helper function to get room status button
        function getRoomStatusButton(room) {
            if (room.status === 'draft') {
                return `
                    <button class="btn btn-sm btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Publish Room" onclick="updateRoomStatus(${room.id}, 'published')">
                        <i class="bi bi-globe"></i> Publish
                    </button>
                `;
            } else if (room.status === 'published') {
                return `
                    <button class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Reserve Room" onclick="updateRoomStatus(${room.id}, 'reserved')">
                        <i class="bi bi-lock"></i> Reserve
                    </button>
                `;
            } else if (room.status === 'reserved') {
                return `
                    <button class="btn btn-sm btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Publish Room" onclick="updateRoomStatus(${room.id}, 'published')">
                        <i class="bi bi-globe"></i> Publish
                    </button>
                `;
            }
            return '';
        }

        // Function to update room status
        function updateRoomStatus(roomId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_room_status');
            formData.append('room_id', roomId);
            formData.append('status', newStatus);
            
            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    loadRooms(); // Refresh the room list silently
                } else {
                    showAlert('error', 'Error', data.message || 'Failed to update room status');
                }
            })
            .catch(error => {
                console.error('Error updating room status:', error);
                showAlert('error', 'Error', 'An error occurred while updating room status: ' + error.message);
            });
        }

        // Function to edit room
        function editRoom(roomId) {
            console.log('Editing room:', roomId);
            console.log('Current hostel ID:', currentHostelId);
            
            // Set the hostel_id in the form
            document.getElementById('hostel_id').value = currentHostelId;
            
            fetch(`get_rooms.php?hostel_id=${currentHostelId}&room_id=${roomId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.rooms && data.rooms.length > 0) {
                        const room = data.rooms[0];
                        console.log('Room details:', room);
                        
                        // Update modal title
                        document.getElementById('roomModalLabel').textContent = 'Edit Room';
                        
                        // Set form values
                        document.getElementById('room_id').value = room.id;
                        document.getElementById('room_code').value = room.room_code;
                        document.getElementById('number_of_beds').value = room.number_of_beds;
                        
                        // Show the modal
                        const roomModal = new bootstrap.Modal(document.getElementById('roomModal'));
                        roomModal.show();
                    } else {
                        console.error('No room data found');
                        showAlert('error', 'Error', 'Room not found');
                    }
                })
                .catch(error => {
                    console.error('Error fetching room details:', error);
                    showAlert('error', 'Error', 'An error occurred while loading room details: ' + error.message);
                });
        }

        // Function to save room
        function saveRoom() {
            const roomId = document.getElementById('room_id').value;
            const hostelId = document.getElementById('hostel_id').value;
            const roomCode = document.getElementById('room_code').value;
            const numberOfBeds = document.getElementById('number_of_beds').value;
            
            console.log('Saving room:', { roomId, hostelId, roomCode, numberOfBeds });
            
            if (!hostelId || !roomCode || !numberOfBeds) {
                console.error('Validation failed: Missing required fields');
                showAlert('error', 'Validation Error', 'Please fill in all required fields: Hostel, Room Code, and Number of Beds');
                return;
            }
            
            if (isNaN(numberOfBeds) || numberOfBeds < 1) {
                console.error('Validation failed: Invalid number of beds');
                showAlert('error', 'Validation Error', 'Number of beds must be a positive number');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', roomId ? 'edit_room' : 'add_room');
            if (roomId) formData.append('room_id', roomId);
            formData.append('hostel_id', hostelId);
            formData.append('room_code', roomCode);
            formData.append('number_of_beds', numberOfBeds);
            
            console.log('Sending request with action:', roomId ? 'edit_room' : 'add_room');
            
            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received response:', data);
                if (data.success) {
                    // Close modal first
                    const roomModal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
                    roomModal.hide();
                    
                    // Show success message
                    showAlert('success', 'Success', data.message, () => {
                        // Reset form
                        document.getElementById('roomForm').reset();
                        document.getElementById('room_id').value = '';
                        
                        // Refresh room list
                        loadRooms();
                    });
                } else {
                    console.error('Operation failed:', data.message);
                    showAlert('error', 'Error', data.message || 'An error occurred while saving the room');
                }
            })
            .catch(error => {
                console.error('Error saving room:', error);
                showAlert('error', 'Error', 'An error occurred while processing your request: ' + error.message);
            });
        }

        // Function to delete room
        function deleteRoom(roomId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_room');
                    formData.append('room_id', roomId);
                    
                    fetch('manage_hostels.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showAlert('success', 'Success', data.message, () => {
                                loadRooms(currentHostelId);
                            });
                        } else {
                            showAlert('error', 'Error', data.message || 'Failed to delete room');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting room:', error);
                        showAlert('error', 'Error', 'An error occurred while deleting the room: ' + error.message);
                    });
                }
            });
        }

        // Function to show hostels for a campus
        function showHostels(campusId) {
            currentCampusId = campusId;
            const hostelsSection = document.getElementById('hostelsSection');
            const roomsSection = document.getElementById('roomsSection');
            
            if (hostelsSection) {
                hostelsSection.style.display = 'block';
            }
            if (roomsSection) {
                roomsSection.style.display = 'none';
            }
            loadHostels();
        }

        // Function to show rooms for a hostel
        function showRooms(hostelId) {
            currentHostelId = hostelId;
            const roomsSection = document.getElementById('roomsSection');
            if (roomsSection) {
                roomsSection.style.display = 'block';
            }
            loadRooms();
        }

        // Function to load hostels
        function loadHostels() {
            if (!currentCampusId) {
                console.log('No campus selected');
                const tbody = document.getElementById('hostelsTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="11" class="text-center">Please select a campus to view hostels</td>
                        </tr>
                    `;
                }
                return;
            }

            console.log('Loading hostels for campus:', currentCampusId);
            fetch(`get_hostels.php?campus_id=${currentCampusId}&view=table`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Received hostels data:', data);
                    const tbody = document.getElementById('hostelsTableBody');
                    if (!tbody) {
                        console.error('Hostels table body not found');
                        return;
                    }
                    
                    tbody.innerHTML = '';

                    if (!data || data.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="11" class="text-center">No hostels found for this campus</td>
                            </tr>
                        `;
                        return;
                    }

                    data.forEach(hostel => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${hostel.name}</td>
                            <td>${hostel.building_code}</td>
                            <td>${hostel.gender === 'M' ? '<i class="bi bi-gender-male text-primary"></i> M' : 
                                hostel.gender === 'F' ? '<i class="bi bi-gender-female text-danger"></i> F' : '-'}</td>
                            <td>${hostel.year ? `Year ${hostel.year}` : '-'}</td>
                            <td>${getStatusBadge(hostel.status)}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details" onclick="viewHostelDetails(${JSON.stringify(hostel).replace(/"/g, '&quot;')})">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Hostel" onclick="editHostel(${hostel.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Hostel" onclick="deleteHostel(${hostel.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View Rooms" onclick="showRooms(${hostel.id})">
                                    <i class="fas fa-door-open"></i> View Rooms
                                </button>
                                ${getHostelStatusButton(hostel)}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Initialize tooltips after adding all rows
                    initializeTooltips();
                })
                .catch(error => {
                    console.error('Error loading hostels:', error);
                    const tbody = document.getElementById('hostelsTableBody');
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="11" class="text-center text-danger">
                                    Error loading hostels. Please try again.
                                </td>
                            </tr>
                        `;
                    }
                });
        }

        // Helper function to get hostel status button
        function getHostelStatusButton(hostel) {
            if (hostel.status === 'draft') {
                return `
                    <button class="btn btn-sm btn-success" data-bs- toggle="tooltip" data-bs-placement="top" title="Publish Hostel" onclick="updateHostelStatus(${hostel.id}, 'published')">
                        <i class="bi bi-globe"></i> Publish
                    </button>
                `;
            } else if (hostel.status === 'published') {
                return `
                    <button class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Set to Draft" onclick="updateHostelStatus(${hostel.id}, 'draft')">
                        <i class="bi bi-clock"></i> Set to Draft
                    </button>
                `;
            }
            return '';
        }

        // Function to update hostel status
        function updateHostelStatus(hostelId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_hostel_status');
            formData.append('hostel_id', hostelId);
            formData.append('status', newStatus);
            
            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    loadHostels(); // Refresh the hostel list silently
                } else {
                    showAlert('error', 'Error', data.message || 'Failed to update hostel status');
                }
            })
            .catch(error => {
                console.error('Error updating hostel status:', error);
                showAlert('error', 'Error', 'An error occurred while updating hostel status: ' + error.message);
            });
        }

        // Function to show add hostel modal
        function showAddHostelModal() {
            if (!currentCampusId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a campus first'
                });
                return;
            }

            const modal = document.getElementById('hostelModal');
            const modalLabel = document.getElementById('hostelModalLabel');
            const form = document.getElementById('hostelForm');
            
            if (!modal || !modalLabel || !form) {
                console.error('Required elements not found:', {
                    modal: !!modal,
                    modalLabel: !!modalLabel,
                    form: !!form
                });
                return;
            }

            // Reset form and clear hidden fields
            form.reset();
            document.getElementById('hostel_id').value = '';
            document.getElementById('campus_id').value = currentCampusId;
            
            // Hide status field and set it to draft
            const statusField = document.getElementById('status').parentElement;
            if (statusField) {
                statusField.style.display = 'none';
            }
            document.getElementById('status').value = 'draft';
            
            // Hide edit-only fields
            const editFields = document.getElementById('editFields');
            if (editFields) {
                editFields.style.display = 'none';
            }
            
            // Update modal title
            modalLabel.textContent = 'Add Hostel';
            
            // Show modal
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }

        // Function to edit hostel
        function editHostel(id) {
            destroyAllTooltips();
            // Get hostel data using POST
            const formData = new FormData();
            formData.append('action', 'get_hostel');
            formData.append('id', id);

            // Show loading state
            Swal.fire({
                title: 'Loading...',
                text: 'Please wait while we load the hostel data',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const hostel = data.data;
                    
                    // Set form values
                    document.getElementById('hostel_id').value = hostel.id;
                    document.getElementById('campus_id').value = hostel.campus_id;
                    document.getElementById('name').value = hostel.name;
                    document.getElementById('building_code').value = hostel.building_code;
                    document.getElementById('othernames').value = hostel.othernames || '';
                    document.getElementById('gender').value = hostel.gender;
                    document.getElementById('year').value = hostel.year;
                    document.getElementById('status').value = hostel.status;
                    
                    // Show edit-only fields
                    const editFields = document.getElementById('editFields');
                    if (editFields) {
                        editFields.style.display = 'block';
                    }
                    
                    // Set edit-only field values
                    document.getElementById('college').value = hostel.college || '';
                    document.getElementById('school').value = hostel.school || '';
                    document.getElementById('disability').value = hostel.disability || '';
                    
                    // Update modal title
                    document.getElementById('hostelModalLabel').textContent = 'Edit Hostel';
                    
                    // Close loading state
                    Swal.close();
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('hostelModal'));
                    modal.show();
                    highlightCurrentValues(hostel);
                } else {
                    throw new Error(data.message || 'Failed to load hostel data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load hostel data: ' + error.message
                });
            });
        }

        // Function to save hostel
        function saveHostel() {
            const form = document.getElementById('hostelForm');
            if (!form) return;

            const formData = new FormData(form);
            const hostelId = formData.get('hostel_id');
            const campusId = formData.get('campus_id');
            
            // Set the correct action based on whether we're adding or editing
            formData.set('action', hostelId ? 'edit_hostel' : 'add_hostel');

            // If editing, ensure we have the ID
            if (hostelId) {
                formData.set('id', hostelId);
            }

            // Validate required fields
            const requiredFields = ['name', 'building_code', 'campus_id', 'gender', 'year'];
            const missingFields = requiredFields.filter(field => !formData.get(field));
            
            if (missingFields.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields: ' + missingFields.join(', ')
                });
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we save the hostel',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('hostelModal'));
                    modal.hide();
                    form.reset();
                    loadHostels();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: hostelId ? 'Hostel updated successfully' : 'Hostel added successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Failed to save hostel');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save hostel: ' + error.message
                });
            });
        }

        // Function to edit campus
        function editCampus(id, name) {
            const modal = new bootstrap.Modal(document.getElementById('addCampusModal'));
            const form = document.querySelector('#addCampusModal form');
            
            // Reset form
            form.reset();
            
            // Set form values
            form.querySelector('[name="action"]').value = 'edit_campus';
            form.querySelector('[name="campus_id"]').value = id;
            form.querySelector('[name="campus_name"]').value = name;
            
            // Update modal title and button
            document.querySelector('#addCampusModal .modal-title').textContent = 'Edit Campus';
            document.querySelector('#addCampusModal button[type="submit"]').textContent = 'Update Campus';
            
            // Show modal
            modal.show();
        }

        // Function to delete campus
        function deleteCampus(id) {
            destroyAllTooltips();
            Swal.fire({
                title: 'Are you sure?',
                text: "This will also delete all associated hostels and rooms!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_campus');
                    formData.append('campus_id', id);
                    
                    fetch('manage_hostels.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || `HTTP error! status: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message || 'Campus has been deleted.'
                            });
                            // Refresh the page to show updated campus list
                            location.reload();
                        } else {
                            throw new Error(data.message || 'Failed to delete campus');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete campus: ' + error.message
                        });
                    });
                }
            });
        }

        // Add event listener for campus form submission
        document.addEventListener('DOMContentLoaded', function() {
            const campusForm = document.querySelector('#addCampusModal form');
            if (campusForm) {
                campusForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveCampus();
                });
            }
        });

        // Function to save campus (add/edit)
        function saveCampus() {
            const form = document.querySelector('#addCampusModal form');
            if (!form) {
                console.error('Campus form not found');
                return;
            }

            const formData = new FormData(form);
            const campusId = formData.get('campus_id');
            const campusName = formData.get('campus_name');

            // Validate campus name
            if (!campusName || campusName.trim().length < 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Campus name must be at least 2 characters long'
                });
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we save the campus',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = document.getElementById('addCampusModal');
                    if (modal) {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Campus saved successfully',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Refresh the page to show updated campus list
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to save campus');
                }
            })
            .catch(error => {
                console.error('Error saving campus:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save campus: ' + error.message
                });
            });
        }

        // Function to show add campus modal
        function showAddCampusModal() {
            const modal = document.getElementById('addCampusModal');
            const form = document.querySelector('#addCampusModal form');
            
            // Reset form
            form.reset();
            
            // Set default values
            form.querySelector('[name="action"]').value = 'add_campus';
            form.querySelector('[name="campus_id"]').value = '';
            
            // Update modal title and button
            document.querySelector('#addCampusModal .modal-title').textContent = 'Add Campus';
            document.querySelector('#addCampusModal button[type="submit"]').textContent = 'Save Campus';
            
            // Show modal
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }

        // Add these helper functions for pagination
        function changePage(page) {
            if (page < 1) return;
            currentPage = page;
            loadRooms();
        }

        function searchRooms() {
            const searchInput = document.getElementById('roomSearch');
            if (searchInput) {
                searchQuery = searchInput.value;
                currentPage = 1;
                loadRooms();
            }
        }

        // Add event listener for room search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('roomSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        searchRooms();
                    }
                });
            }
        });

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listener for campus selection
            const campusSelect = document.getElementById('campus_id');
            if (campusSelect) {
                campusSelect.addEventListener('change', function() {
                    currentCampusId = this.value;
                    loadHostels();
                });
            }
            
            // Add event listener for hostel form
            const hostelForm = document.getElementById('hostelForm');
            if (hostelForm) {
                hostelForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveHostel();
                });
            }
        });

        // Function to show add room modal
        function showAddRoomModal() {
            if (!currentHostelId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a hostel first'
                });
                return;
            }

            const modal = document.getElementById('roomModal');
            const modalLabel = document.getElementById('roomModalLabel');
            const form = document.getElementById('roomForm');
            
            if (!modal || !modalLabel || !form) {
                console.error('Required elements not found:', {
                    modal: !!modal,
                    modalLabel: !!modalLabel,
                    form: !!form
                });
                return;
            }

            // Reset form and clear hidden fields
            form.reset();
            document.getElementById('room_id').value = '';
            document.getElementById('hostel_id').value = currentHostelId;
            
            // Update modal title
            modalLabel.textContent = 'Add Room';
            
            // Show modal
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }

        // Function to view campus details
        function viewCampusDetails(id, name, createdBy, createdAt, updatedBy, updatedAt) {
            const detailsContent = document.getElementById('detailsContent');
            detailsContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td>${id}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>${name}</td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>${createdBy}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>${formatDateTime(createdAt)}</td>
                        </tr>
                        <tr>
                            <th>Updated By:</th>
                            <td>${updatedBy}</td>
                        </tr>
                        <tr>
                            <th>Updated At:</th>
                            <td>${formatDateTime(updatedAt)}</td>
                        </tr>
                    </table>
                </div>
            `;
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        }

        // Helper function to get status badge
        function getStatusBadge(status) {
            const statusConfig = {
              
                'published': { class: 'primary', icon: 'bi-globe', text: 'Published' },
                'draft': { class: 'warning', icon: 'bi-clock', text: 'Draft' }
            };
            const config = statusConfig[status] || { class: 'secondary', icon: 'bi-question-circle', text: 'Unknown' };
            return `<span class="badge bg-${config.class}"><i class="bi ${config.icon}"></i> ${config.text}</span>`;
        }

        // Helper function to get disability badge
        function getDisabilityBadge(disability) {
            if (disability === '1' || disability === 1) {
                return `<span class="badge bg-success"><i class="bi bi-wheelchair"></i> Accessible</span>`;
            } else if (disability === '0' || disability === 0) {
                return `<span class="badge bg-secondary"><i class="bi bi-slash-circle"></i> Not Accessible</span>`;
            }
            return `<span class="badge bg-secondary"><i class="bi bi-question-circle"></i> Not Specified</span>`;
        }

        // Function to view hostel details
        function viewHostelDetails(hostel) {
            destroyAllTooltips();
            const detailsContent = document.getElementById('detailsContent');
            
            // Create student indicators section with improved design
            const studentIndicators = `
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-people-fill me-1"></i>Student Indicators</h6>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-gender-ambiguous ${hostel.gender === 'M' ? 'text-primary' : hostel.gender === 'F' ? 'text-danger' : 'text-secondary'} fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Gender</h6>
                                        <p class="mb-0">
                                            ${hostel.gender === 'M' ? 
                                                '<i class="bi bi-gender-male text-primary"></i> Male' : 
                                                hostel.gender === 'F' ? 
                                                '<i class="bi bi-gender-female text-danger"></i> Female' : 
                                                '<i class="bi bi-question-circle text-secondary"></i> Not specified'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            ${hostel.college ? `
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-building text-primary fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">College</h6>
                                        <p class="mb-0">${hostel.college.split(',').map(c => `<span class="badge bg-primary me-1">${c}</span>`).join('')}</p>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            ${hostel.school ? `
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-mortarboard text-primary fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">School</h6>
                                        <p class="mb-0">${hostel.school.split(',').map(s => `<span class="badge bg-primary me-1">${s}</span>`).join('')}</p>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            ${hostel.year ? `
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar3 text-primary fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Year</h6>
                                        <p class="mb-0">${hostel.year.split(',').map(y => `<span class="badge bg-primary me-1">Year ${y}</span>`).join('')}</p>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-wheelchair text-primary fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Disability Access</h6>
                                        <p class="mb-0">
                                            ${hostel.disability === '1' || hostel.disability === 1 ? 
                                                '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Accessible</span>' : 
                                                hostel.disability === '0' || hostel.disability === 0 ? 
                                                '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Not Accessible</span>' : 
                                                '<span class="badge bg-secondary"><i class="bi bi-question-circle"></i> Not Specified</span>'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            ${hostel.intake ? `
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-event text-primary fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Intake</h6>
                                        <p class="mb-0">${hostel.intake.split(',').map(i => `<span class="badge bg-primary me-1">${i}</span>`).join('')}</p>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            // Create hostel details section
            const hostelDetails = `
                <div class="card">
                    <div class="card-header bg-primary text-white py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-building me-1"></i>Hostel Details</h6>
                            ${getStatusBadge(hostel.status)}
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-hash text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">ID</small>
                                        <small class="d-block">${hostel.id}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-building text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Name</small>
                                        <small class="d-block">${hostel.name}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-upc-scan text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Building Code</small>
                                        <small class="d-block">${hostel.building_code}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-tag text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Other Names</small>
                                        <small class="d-block">${hostel.othernames || '<i class="bi bi-dash text-secondary"></i> Not specified'}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-geo-alt text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Campus</small>
                                        <small class="d-block">${hostel.campus_name}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-person-plus text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Created By</small>
                                        <small class="d-block">${hostel.created_by || '<i class="bi bi-dash text-secondary"></i> Unknown'}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-calendar-plus text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Created At</small>
                                        <small class="d-block">${formatDateTime(hostel.createdAt)}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-person-check text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Updated By</small>
                                        <small class="d-block">${hostel.updated_by || '<i class="bi bi-dash text-secondary"></i> Unknown'}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-calendar-check text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Updated At</small>
                                        <small class="d-block">${formatDateTime(hostel.updatedAt)}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Combine both sections
            detailsContent.innerHTML = studentIndicators + hostelDetails;
            
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        }

        // Function to view room details
        function viewRoomDetails(room) {
            const detailsContent = document.getElementById('detailsContent');
            detailsContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td>${room.id}</td>
                        </tr>
                        <tr>
                            <th>Room Code:</th>
                            <td>${room.room_code}</td>
                        </tr>
                        <tr>
                            <th>Number of Beds:</th>
                            <td>${room.number_of_beds}</td>
                        </tr>
                        <tr>
                            <th>Available Beds:</th>
                            <td>${room.remain}</td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>${room.created_by || 'Unknown'}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>${formatDateTime(room.createdAt)}</td>
                        </tr>
                        <tr>
                            <th>Updated By:</th>
                            <td>${room.updated_by || 'Unknown'}</td>
                        </tr>
                        <tr>
                            <th>Updated At:</th>
                            <td>${formatDateTime(room.updatedAt)}</td>
                        </tr>
                    </table>
                </div>
            `;
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        }

        // Function to destroy all tooltips
        function destroyAllTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (tooltip) {
                    tooltip.dispose();
                }
            });
        }

        // Function to initialize tooltips with proper options
        function initializeTooltips() {
            destroyAllTooltips(); // First destroy any existing tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover',
                    html: true,
                    delay: { show: 100, hide: 100 }
                });
            });
        }

        // Function to delete hostel
        function deleteHostel(id) {
            destroyAllTooltips();
            Swal.fire({
                title: 'Are you sure?',
                text: "This will also delete all rooms in this hostel!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_hostel');
                    formData.append('id', id);

                    fetch('manage_hostels.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadHostels();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Hostel deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            throw new Error(data.message || 'Failed to delete hostel');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete hostel: ' + error.message
                        });
                    });
                }
            });
        }

        // Function to handle intake management
        document.getElementById('addIntakeBtn')?.addEventListener('click', function() {
            const intakeInput = document.getElementById('intake');
            const intakeList = document.getElementById('intakeList');
            const intakeValue = intakeInput.value.trim();
            
            if (intakeValue) {
                // Create new badge
                const badge = document.createElement('div');
                badge.className = 'badge bg-primary me-2 mb-2';
                badge.textContent = intakeValue;
                
                // Add delete button
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn-close btn-close-white ms-2';
                deleteBtn.style.fontSize = '0.5rem';
                deleteBtn.onclick = function() {
                    badge.remove();
                };
                
                badge.appendChild(deleteBtn);
                intakeList.appendChild(badge);
                intakeInput.value = '';
            }
        });

        // Function to validate year input
        document.getElementById('year')?.addEventListener('input', function(e) {
            // Remove any non-numeric characters except commas
            this.value = this.value.replace(/[^0-9,]/g, '');
            
            // Remove consecutive commas
            this.value = this.value.replace(/,+/g, ',');
            
            // Remove leading and trailing commas
            this.value = this.value.replace(/^,|,$/g, '');
        });

        // Function to handle year selection
        function updateYearValue() {
            const selectedYears = Array.from(document.querySelectorAll('input[name="year[]"]:checked'))
                .map(cb => cb.value)
                .sort()
                .join(',');
            document.getElementById('year').value = selectedYears;
        }

        // Function to handle intake selection
        function updateIntakeValue() {
            const selectedIntakes = Array.from(document.querySelectorAll('input[name="intake[]"]:checked'))
                .map(cb => cb.value)
                .sort()
                .join(',');
            document.getElementById('intake').value = selectedIntakes;
        }

        // Add event listeners for year checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[name="year[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateYearValue);
            });

            document.querySelectorAll('input[name="intake[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateIntakeValue);
            });
        });

        // Function to highlight current values when editing
        function highlightCurrentValues(hostel) {
            if (hostel.year) {
                const years = hostel.year.split(',');
                years.forEach(year => {
                    const checkbox = document.querySelector(`input[name="year[]"][value="${year}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                updateYearValue();
            }

            if (hostel.intake) {
                const intakes = hostel.intake.split(',');
                intakes.forEach(intake => {
                    const checkbox = document.querySelector(`input[name="intake[]"][value="${intake}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                updateIntakeValue();
            }
        }

        // Add these JavaScript functions for college and school
        // Function to handle college selection
        function updateCollegeValue() {
            const selectedColleges = Array.from(document.querySelectorAll('input[name="college[]"]:checked'))
                .map(cb => cb.value)
                .sort()
                .join(',');
            document.getElementById('college').value = selectedColleges;
        }

        // Function to handle school selection
        function updateSchoolValue() {
            const selectedSchools = Array.from(document.querySelectorAll('input[name="school[]"]:checked'))
                .map(cb => cb.value)
                .sort()
                .join(',');
            document.getElementById('school').value = selectedSchools;
        }

        // Update the DOMContentLoaded event listener
        document.addEventListener('DOMContentLoaded', function() {
            // Existing event listeners
            document.querySelectorAll('input[name="year[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateYearValue);
            });

            document.querySelectorAll('input[name="intake[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateIntakeValue);
            });

            // New event listeners for college and school
            document.querySelectorAll('input[name="college[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateCollegeValue);
            });

            document.querySelectorAll('input[name="school[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateSchoolValue);
            });
        });

        // Update the highlightCurrentValues function
        function highlightCurrentValues(hostel) {
            // Existing year and intake highlighting
            if (hostel.year) {
                const years = hostel.year.split(',');
                years.forEach(year => {
                    const checkbox = document.querySelector(`input[name="year[]"][value="${year}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                updateYearValue();
            }

            if (hostel.intake) {
                const intakes = hostel.intake.split(',');
                intakes.forEach(intake => {
                    const checkbox = document.querySelector(`input[name="intake[]"][value="${intake}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                updateIntakeValue();
            }

            // New college and school highlighting
            if (hostel.college) {
                const colleges = hostel.college.split(',');
                colleges.forEach(college => {
                    const checkbox = document.querySelector(`input[name="college[]"][value="${college}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                updateCollegeValue();
            }

            if (hostel.school) {
                const schools = hostel.school.split(',');
                schools.forEach(school => {
                    const checkbox = document.querySelector(`input[name="school[]"][value="${school}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                updateSchoolValue();
            }
        }
    </script>
</body>
</html> 