<?php
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_hostel':
                // Validate required fields
                if (empty($_POST['name']) || empty($_POST['building_code']) || empty($_POST['campus_id'])) {
                    throw new Exception('Name, Building Code, and Campus are required fields');
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

                // Insert new hostel with user tracking
                $stmt = $connection->prepare("INSERT INTO hostels (name, building_code, othernames, gender, year, campus_id, status, createdBy, updatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssissi", 
                    $_POST['name'],
                    $_POST['building_code'],
                    $_POST['othernames'],
                    $_POST['gender'],
                    $_POST['year'],
                    $_POST['campus_id'],
                    'draft',  // Set default status to draft
                    $_SESSION['id'],
                    $_SESSION['id']
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

                // Check if hostel name already exists in the campus (excluding current hostel)
                $stmt = $connection->prepare("SELECT id FROM hostels WHERE name = ? AND campus_id = ? AND id != ?");
                $stmt->bind_param("sii", $_POST['name'], $_POST['campus_id'], $_POST['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A hostel with this name already exists in this campus');
                }

                // Check if building code already exists in the campus (excluding current hostel)
                $stmt = $connection->prepare("SELECT id FROM hostels WHERE building_code = ? AND campus_id = ? AND id != ?");
                $stmt->bind_param("sii", $_POST['building_code'], $_POST['campus_id'], $_POST['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception('A hostel with this building code already exists in this campus');
                }

                // Update hostel with user tracking
                $stmt = $connection->prepare("UPDATE hostels SET name = ?, building_code = ?, othernames = ?, gender = ?, year = ?, campus_id = ?, college = ?, school = ?, disability = ?, status = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sssssissisii", 
                    $_POST['name'],
                    $_POST['building_code'],
                    $_POST['othernames'],
                    $_POST['gender'],
                    $_POST['year'],
                    $_POST['campus_id'],
                    $_POST['college'],
                    $_POST['school'],
                    $_POST['disability'],
                    $_POST['status'],
                    $_SESSION['id'],
                    $_POST['id']
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

            case 'delete_hostel':
                // Validate required fields
                if (empty($_POST['id'])) {
                    throw new Exception('Hostel ID is required');
                }

                // Start transaction
                $connection->begin_transaction();

                try {
                    // Delete associated rooms first
                    $stmt = $connection->prepare("DELETE FROM rooms WHERE hostel_id = ?");
                    $stmt->bind_param("i", $_POST['id']);
                    $stmt->execute();

                    // Delete the hostel
                    $stmt = $connection->prepare("DELETE FROM hostels WHERE id = ?");
                    $stmt->bind_param("i", $_POST['id']);
                    $stmt->execute();

                    // Commit transaction
                    $connection->commit();

                    echo json_encode([
                        'success' => true,
                        'message' => 'Hostel and associated rooms deleted successfully'
                    ]);
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $connection->rollback();
                    throw new Exception('Failed to delete hostel: ' . $e->getMessage());
                }
                break;
                
            case 'add_room':
                // Validate required fields
                if (empty($_POST['room_code']) || empty($_POST['number_of_beds']) || empty($_POST['hostel_id'])) {
                    throw new Exception('All required fields must be filled');
                }
                
                $room_code = mysqli_real_escape_string($connection, $_POST['room_code']);
                $number_of_beds = (int)$_POST['number_of_beds'];
                $hostel_id = (int)$_POST['hostel_id'];
                
                // Validate number of beds
                if ($number_of_beds < 1) {
                    throw new Exception('Number of beds must be greater than 0');
                }
                
                // Check if room code already exists in the hostel
                $check_query = "SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ?";
                $check_stmt = mysqli_prepare($connection, $check_query);
                mysqli_stmt_bind_param($check_stmt, "si", $room_code, $hostel_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    throw new Exception('Room code already exists in this hostel');
                }
                
                // Insert new room with user tracking
                $query = "INSERT INTO rooms (room_code, number_of_beds, hostel_id, remain, createdBy, updatedBy) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "siiiii", 
                    $room_code, 
                    $number_of_beds, 
                    $hostel_id, 
                    $number_of_beds,
                    $_SESSION['id'],
                    $_SESSION['id']
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Room added successfully'
                ]);
                break;
                
            case 'edit_room':
                // Validate required fields
                if (empty($_POST['room_code']) || empty($_POST['number_of_beds']) || empty($_POST['hostel_id'])) {
                    throw new Exception('All required fields must be filled');
                }
                
                $room_code = mysqli_real_escape_string($connection, $_POST['room_code']);
                $number_of_beds = (int)$_POST['number_of_beds'];
                $hostel_id = (int)$_POST['hostel_id'];
                $room_id = (int)$_POST['room_id'];
                
                // Validate number of beds
                if ($number_of_beds < 1) {
                    throw new Exception('Number of beds must be greater than 0');
                }
                
                // Check if room code already exists in other rooms of the same hostel
                $check_query = "SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ? AND id != ?";
                $check_stmt = mysqli_prepare($connection, $check_query);
                mysqli_stmt_bind_param($check_stmt, "sii", $room_code, $hostel_id, $room_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    throw new Exception('Room code already exists in this hostel');
                }
                
                // Get current room data
                $current_query = "SELECT number_of_beds, remain FROM rooms WHERE id = ?";
                $current_stmt = mysqli_prepare($connection, $current_query);
                mysqli_stmt_bind_param($current_stmt, "i", $room_id);
                mysqli_stmt_execute($current_stmt);
                $current_result = mysqli_stmt_get_result($current_stmt);
                $current_room = mysqli_fetch_assoc($current_result);
                
                if (!$current_room) {
                    throw new Exception('Room not found');
                }
                
                // Calculate new remaining beds
                $bed_difference = $number_of_beds - $current_room['number_of_beds'];
                $new_remain = $current_room['remain'] + $bed_difference;
                
                // Ensure remaining beds doesn't go below 0
                $new_remain = max(0, $new_remain);
                
                // Update room with user tracking
                $query = "UPDATE rooms SET room_code = ?, number_of_beds = ?, remain = ?, updatedBy = ? WHERE id = ?";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "siiii", 
                    $room_code, 
                    $number_of_beds, 
                    $new_remain,
                    $_SESSION['id'],
                    $room_id
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Room updated successfully'
                ]);
                break;
                
            case 'delete_room':
                if (empty($_POST['room_id'])) {
                    throw new Exception('Room ID is required');
                }
                
                $room_id = (int)$_POST['room_id'];
                
                // Check if room exists and get hostel_id
                $check_query = "SELECT hostel_id FROM rooms WHERE id = ?";
                $check_stmt = mysqli_prepare($connection, $check_query);
                mysqli_stmt_bind_param($check_stmt, "i", $room_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) === 0) {
                    throw new Exception('Room not found');
                }
                
                // Delete the room
                $delete_query = "DELETE FROM rooms WHERE id = ?";
                $delete_stmt = mysqli_prepare($connection, $delete_query);
                mysqli_stmt_bind_param($delete_stmt, "i", $room_id);
                
                if (!mysqli_stmt_execute($delete_stmt)) {
                    throw new Exception('Database error: ' . mysqli_stmt_error($delete_stmt));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Room deleted successfully'
                ]);
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

                // Insert new campus with user tracking
                $stmt = $connection->prepare("INSERT INTO campuses (name, createdBy, updatedBy) VALUES (?, ?, ?)");
                $stmt->bind_param("sii", 
                    $_POST['campus_name'],
                    $_SESSION['id'],
                    $_SESSION['id']
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

                // Update campus with user tracking
                $stmt = $connection->prepare("UPDATE campuses SET name = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sii", 
                    $_POST['campus_name'],
                    $_SESSION['id'],
                    $_POST['campus_id']
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

                // Start transaction
                $connection->begin_transaction();

                try {
                    // Delete associated rooms first
                    $stmt = $connection->prepare("
                        DELETE r FROM rooms r 
                        INNER JOIN hostels h ON r.hostel_id = h.id 
                        WHERE h.campus_id = ?
                    ");
                    $stmt->bind_param("i", $_POST['campus_id']);
                    $stmt->execute();

                    // Delete associated hostels
                    $stmt = $connection->prepare("DELETE FROM hostels WHERE campus_id = ?");
                    $stmt->bind_param("i", $_POST['campus_id']);
                    $stmt->execute();

                    // Delete the campus
                    $stmt = $connection->prepare("DELETE FROM campuses WHERE id = ?");
                    $stmt->bind_param("i", $_POST['campus_id']);
                    $stmt->execute();

                    // Commit transaction
                    $connection->commit();

                    echo json_encode([
                        'success' => true,
                        'message' => 'Campus and all associated hostels and rooms deleted successfully'
                    ]);
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $connection->rollback();
                    throw new Exception('Failed to delete campus: ' . $e->getMessage());
                }
                break;

            case 'update_status':
                if (empty($_POST['id']) || empty($_POST['status'])) {
                    throw new Exception('Hostel ID and status are required');
                }

                $valid_statuses = ['draft', 'published'];
                if (!in_array($_POST['status'], $valid_statuses)) {
                    throw new Exception('Invalid status value');
                }

                $stmt = $connection->prepare("UPDATE hostels SET status = ?, updatedBy = ? WHERE id = ?");
                $stmt->bind_param("sii", 
                    $_POST['status'],
                    $_SESSION['id'],
                    $_POST['id']
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Status updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update status: ' . $stmt->error);
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
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
                                            <th>Other Names</th>
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
                            <select class="form-select" id="year" name="year" required>
                                <option value="">Select Year</option>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                                <option value="5">Year 5</option>
                            </select>
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
                                <label for="college" class="form-label">College</label>
                                <select class="form-select" id="college" name="college">
                                    <option value="">Select College</option>
                                    <?php
                                    $colleges_query = mysqli_query($connection, "SELECT DISTINCT college FROM info WHERE college IS NOT NULL AND college != '' ORDER BY college");
                                    while ($college = mysqli_fetch_assoc($colleges_query)) {
                                        echo "<option value='" . htmlspecialchars($college['college']) . "'>" . htmlspecialchars($college['college']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="school" class="form-label">School</label>
                                <select class="form-select" id="school" name="school">
                                    <option value="">Select School</option>
                                    <?php
                                    $schools_query = mysqli_query($connection, "SELECT DISTINCT school FROM info WHERE school IS NOT NULL AND school != '' ORDER BY school");
                                    while ($school = mysqli_fetch_assoc($schools_query)) {
                                        echo "<option value='" . htmlspecialchars($school['school']) . "'>" . htmlspecialchars($school['school']) . "</option>";
                                    }
                                    ?>
                                </select>
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
                            <td colspan="8" class="text-center">Please select a hostel to view rooms</td>
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
                                <td colspan="8" class="text-center">No rooms found for this hostel</td>
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
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Update pagination if it exists
                    const pagination = document.getElementById('roomPagination');
                    if (pagination && data.total_pages) {
                        let html = '';
                        
                        // Previous button
                        html += `
                            <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                                <a class="page-link" href="#" onclick="changePage(${data.current_page - 1})">Previous</a>
                            </li>
                        `;
                        
                        // Page numbers
                        for (let i = 1; i <= data.total_pages; i++) {
                            html += `
                                <li class="page-item ${data.current_page === i ? 'active' : ''}">
                                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                                </li>
                            `;
                        }
                        
                        // Next button
                        html += `
                            <li class="page-item ${data.current_page === data.total_pages ? 'disabled' : ''}">
                                <a class="page-link" href="#" onclick="changePage(${data.current_page + 1})">Next</a>
                            </li>
                        `;
                        
                        pagination.innerHTML = html;
                    }

                    // Update pagination info if elements exist
                    const startElement = document.getElementById('roomStart');
                    const endElement = document.getElementById('roomEnd');
                    const totalElement = document.getElementById('roomTotal');
                    
                    if (startElement) startElement.textContent = data.start;
                    if (endElement) endElement.textContent = data.end;
                    if (totalElement) totalElement.textContent = data.total;

                    // Initialize tooltips for dynamically added elements
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                    const tbody = document.getElementById('roomsTableBody');
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center text-danger">
                                    Error loading rooms. Please try again.
                                </td>
                            </tr>
                        `;
                    }
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
                            <td>${hostel.othernames || '-'}</td>
                            <td>${hostel.gender === 'M' ? '<i class="bi bi-gender-male text-primary"></i> Male' : 
                                hostel.gender === 'F' ? '<i class="bi bi-gender-female text-danger"></i> Female' : '-'}</td>
                            <td>${hostel.year || '-'}</td>
                          
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
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Initialize tooltips for dynamically added elements
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
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
            console.log('Editing hostel:', id);
            fetch(`get_hostel_details.php?id=${id}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received hostel data:', data);
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load hostel details');
                    }

                    const hostel = data.hostel;
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

                    // Populate form fields
                    document.getElementById('hostel_id').value = hostel.id;
                    document.getElementById('name').value = hostel.name;
                    document.getElementById('building_code').value = hostel.building_code;
                    document.getElementById('othernames').value = hostel.othernames || '';
                    document.getElementById('gender').value = hostel.gender;
                    document.getElementById('year').value = hostel.year || '';
                    document.getElementById('campus_id').value = hostel.campus_id;
                    
                    // Show and set status field
                    const statusField = document.getElementById('status').parentElement;
                    if (statusField) {
                        statusField.style.display = 'block';
                    }
                    document.getElementById('status').value = hostel.status || 'draft';
                    
                    // Show and populate edit-only fields
                    const editFields = document.getElementById('editFields');
                    if (editFields) {
                        editFields.style.display = 'block';
                        document.getElementById('college').value = hostel.college || '';
                        document.getElementById('school').value = hostel.school || '';
                        document.getElementById('disability').value = hostel.disability || '';
                    }
                    
                    // Store current campus ID
                    currentCampusId = hostel.campus_id;
                    
                    // Update modal title
                    modalLabel.textContent = 'Edit Hostel';
                    
                    // Show modal
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                })
                .catch(error => {
                    console.error('Error loading hostel details:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load hostel details: ' + error.message
                    });
                });
        }

        // Function to save hostel
        function saveHostel() {
            const form = document.getElementById('hostelForm');
            if (!form) {
                console.error('Hostel form not found');
                return;
            }

            // Get form data
            const formData = new FormData(form);
            const hostelId = formData.get('hostel_id');
            const name = formData.get('name');
            const buildingCode = formData.get('building_code');
            const campusId = formData.get('campus_id');
            const gender = formData.get('gender');
            const year = formData.get('year');
            
            // Set the correct action based on whether we're adding or editing
            formData.set('action', hostelId ? 'edit_hostel' : 'add_hostel');

            // If editing, ensure we have the ID and campus_id
            if (hostelId) {
                formData.set('id', hostelId); // Add the ID for edit operation
                if (!campusId) {
                    formData.set('campus_id', currentCampusId);
                }
            }

            // Validate required fields
            if (!name || !buildingCode || !campusId || !gender || !year) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields (Name, Building Code, Campus, Gender, and Year)'
                });
                return;
            }

            // Validate name format
            if (name.trim().length < 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Hostel name must be at least 2 characters long'
                });
                return;
            }

            // Validate building code format
            if (buildingCode.trim().length < 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Building code must be at least 2 characters long'
                });
                return;
            }

            // Strict validation for gender
            if (gender !== 'M' && gender !== 'F') {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Gender must be either Male (M) or Female (F)'
                });
                return;
            }

            // Validate year value
            if (!['1', '2', '3', '4', '5'].includes(year)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a valid year (1-5)'
                });
                return;
            }

            // Additional validation for edit operation
            if (hostelId && !formData.get('id')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Hostel ID is missing'
                });
                return;
            }

            console.log('Saving hostel:', {
                action: formData.get('action'),
                hostelId,
                formData: Object.fromEntries(formData)
            });

            fetch('manage_hostels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Save response:', data);
                if (data.success) {
                    // Close modal
                    const modal = document.getElementById('hostelModal');
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
                        text: data.message || 'Hostel saved successfully'
                    });

                    // Refresh hostel list
                    loadHostels();
                } else {
                    throw new Error(data.message || 'Failed to save hostel');
                }
            })
            .catch(error => {
                console.error('Error saving hostel:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save hostel: ' + error.message
                });
            });
        }

        // Function to delete hostel
        function deleteHostel(id) {
            console.log('Deleting hostel:', id);
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
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || `HTTP error! status: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Delete response:', data);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message || 'Hostel has been deleted.'
                            });
                            loadHostels();
                        } else {
                            throw new Error(data.message || 'Failed to delete hostel');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting hostel:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete hostel: ' + error.message
                        });
                    });
                }
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
                        text: data.message || 'Campus saved successfully'
                    });

                    // Refresh the page to show updated campus list
                    location.reload();
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
            const detailsContent = document.getElementById('detailsContent');
            
            // Create student indicators section
            const studentIndicators = `
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-people-fill me-1"></i>Student Indicators</h6>
                            <small class="badge bg-light text-primary">${hostel.year ? `Year ${hostel.year}` : 'Not specified'}</small>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-gender-ambiguous ${hostel.gender === 'M' ? 'text-primary' : hostel.gender === 'F' ? 'text-danger' : 'text-secondary'} me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Gender</small>
                                        <small class="d-block">
                                            ${hostel.gender === 'M' ? 
                                                '<i class="bi bi-gender-male text-primary"></i> Male' : 
                                                hostel.gender === 'F' ? 
                                                '<i class="bi bi-gender-female text-danger"></i> Female' : 
                                                '<i class="bi bi-question-circle text-secondary"></i> Not specified'}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-building text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">College</small>
                                        <small class="d-block">${hostel.college || '<i class="bi bi-dash text-secondary"></i> Not specified'}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-mortarboard text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">School</small>
                                        <small class="d-block">${hostel.school || '<i class="bi bi-dash text-secondary"></i> Not specified'}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-calendar3 text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Year</small>
                                        <small class="d-block">${hostel.year ? `Year ${hostel.year}` : '<i class="bi bi-dash text-secondary"></i> Not specified'}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-wheelchair text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Disability Access</small>
                                        <small class="d-block">
                                            ${hostel.disability === '1' || hostel.disability === 1 ? 
                                                '<i class="bi bi-check-circle text-success"></i> Accessible' : 
                                                hostel.disability === '0' || hostel.disability === 0 ? 
                                                '<i class="bi bi-x-circle text-danger"></i> Not Accessible' : 
                                                '<i class="bi bi-question-circle text-secondary"></i> Not Specified'}
                                        </small>
                                    </div>
                                </div>
                            </div>
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

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html> 