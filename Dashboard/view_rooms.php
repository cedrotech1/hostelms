<?php
// Start output buffering
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Include necessary files
require_once('connection.php');


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error handler
function handleError($errno, $errstr, $errfile, $errline) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
}
set_error_handler('handleError');

// Set exception handler
function handleException($e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
set_exception_handler('handleException');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    } else {
        header('Location: index.php');
    }
    exit();
}

// Get hostel_id from URL or POST data
$hostel_id = isset($_GET['hostel_id']) ? intval($_GET['hostel_id']) : (isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0);

// Validate database connection
if (!isset($connection) || !$connection || $connection->connect_error) {
    throw new Exception('Database connection failed: ' . ($connection ? $connection->connect_error : 'Connection not established'));
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_GET['action'])) {
    // Clear any previous output
    ob_clean();
    
    try {
        // Set JSON header
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        $response = ['success' => false, 'message' => ''];
        
        // Validate action
        if (empty($action)) {
            throw new Exception('No action specified');
        }
        
        // Validate hostel_id for actions that require it
        $actionsRequiringHostelId = ['get_rooms', 'add_room'];
        if (in_array($action, $actionsRequiringHostelId) && empty($hostel_id)) {
            throw new Exception('Hostel ID is required');
        }
        
        switch ($action) {
            case 'get_rooms':
                if (empty($hostel_id)) {
                    throw new Exception('Hostel ID is required');
                }
                
                $query = "SELECT r.*, 
                                 r.remain as available_beds,
                                 (r.number_of_beds - r.remain) as assigned_beds
                          FROM rooms r 
                          WHERE r.hostel_id = ? 
                          ORDER BY r.room_code";
                
                $stmt = $connection->prepare($query);
                $stmt->bind_param("i", $hostel_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $rooms = [];
                
                while ($row = $result->fetch_assoc()) {
                    $rooms[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Rooms loaded successfully',
                    'rooms' => $rooms
                ];
                break;
                
            case 'get_room':
                $room_id = $_POST['room_id'] ?? $_GET['room_id'] ?? null;
                if (empty($room_id)) throw new Exception('Room ID is required');

                $stmt = $connection->prepare("SELECT * FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $room = $stmt->get_result()->fetch_assoc();

                if ($room) {
                    $response = [
                        'success' => true, 
                        'message' => 'Room loaded successfully',
                        'room' => $room
                    ];
                } else {
                    throw new Exception('Room not found');
                }
                break;

            case 'add_room':
                if (empty($hostel_id) || empty($_POST['room_code']) || empty($_POST['number_of_beds'])) {
                    throw new Exception('Room Code and Number of Beds are required');
                }

                $room_code = trim(mysqli_real_escape_string($connection, $_POST['room_code']));
                $number_of_beds = max(1, intval($_POST['number_of_beds']));
                $status = 'published';
                $room_code2 = trim(mysqli_real_escape_string($connection, $_POST['room_code2']));
                $createdBy = $updatedBy = $_SESSION['id'];
                $createdAt = date('Y-m-d H:i:s');
                
                // Check if room already exists
                $check = $connection->prepare("SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ?");
                $check->bind_param("si", $room_code, $hostel_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception('A room with this code already exists in this hostel');
                }
                
                // Insert new room
                $stmt = $connection->prepare("INSERT INTO rooms (hostel_id, room_code, room_code2, number_of_beds, remain, status, createdBy, updatedBy, createdAt, updatedAt) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssssss", $hostel_id, $room_code, $room_code2, $number_of_beds, $number_of_beds, $status, $createdBy, $updatedBy, $createdAt, $createdAt);
                
                if ($stmt->execute()) {
                    $room_id = $connection->insert_id;
                    $response = [
                        'success' => true, 
                        'message' => 'Room added successfully',
                        'room' => [
                            'id' => $room_id,
                            'hostel_id' => $hostel_id,
                            'room_code' => $room_code,
                            'room_code2' => $room_code2,
                            'number_of_beds' => $number_of_beds,
                            'remain' => $number_of_beds,
                            'status' => $status,
                            'createdAt' => $createdAt
                        ]
                    ];
                    // ajax re-load
                    
                } else {
                    throw new Exception('Failed to add room: ' . $connection->error);
                }
                break;

            case 'edit_room':
                $room_id = $_POST['room_id'] ?? 0;
                if (empty($room_id) || empty($_POST['room_code']) || empty($_POST['number_of_beds'])) {
                    throw new Exception('All fields are required');
                }

                $room_code = trim(mysqli_real_escape_string($connection, $_POST['room_code']));
                $room_code2 = trim(mysqli_real_escape_string($connection, $_POST['room_code2']));
                $new_number_of_beds = max(1, intval($_POST['number_of_beds']));
                $updatedBy = $_SESSION['id'];
                $updatedAt = date('Y-m-d H:i:s');
                
                // Get current room data
                $stmt = $connection->prepare("SELECT number_of_beds, remain FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    throw new Exception('Room not found');
                }
                
                $room = $result->fetch_assoc();
                $current_beds = $room['number_of_beds'];
                $current_remain = $room['remain'];
                
                // Check if room code is already used by another room in this hostel
                $check = $connection->prepare("SELECT id FROM rooms WHERE room_code = ? AND hostel_id = ? AND id != ?");
                $check->bind_param("sii", $room_code, $hostel_id, $room_id);
                $check->execute();
                
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception('A room with this code already exists in this hostel');
                }
                
                // Calculate new remain value if number of beds changes
                $new_remain = $current_remain;
                if ($new_number_of_beds != $current_beds) {
                    $new_remain = max(0, $current_remain + ($new_number_of_beds - $current_beds));
                }
                
                // Update room
                $stmt = $connection->prepare("UPDATE rooms SET 
                                           room_code = ?, 
                                           room_code2 = ?, 
                                           number_of_beds = ?, 
                                           remain = ?, 
                                           updatedBy = ?, 
                                           updatedAt = ? 
                                           WHERE id = ?");
                $stmt->bind_param("ssiiisi", $room_code, $room_code2, $new_number_of_beds, $new_remain, $updatedBy, $updatedAt, $room_id);
                
                if ($stmt->execute()) {
                    $response = [
                        'success' => true, 
                        'message' => 'Room updated successfully',
                        'room' => [
                            'id' => $room_id,
                            'room_code' => $room_code,
                            'room_code2' => $room_code2,
                            'number_of_beds' => $new_number_of_beds,
                            'remain' => $new_remain,
                            'updatedAt' => $updatedAt
                        ]
                    ];
                } else {
                    throw new Exception('Failed to update room: ' . $connection->error);
                }
                break;

            case 'delete_room':
                $room_id = $_POST['room_id'] ?? 0;
                if (empty($room_id)) {
                    throw new Exception('Room ID is required');
                }
                
                // Check if room has any assigned beds
                $stmt = $connection->prepare("SELECT number_of_beds, remain FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    throw new Exception('Room not found');
                }
                
                $room = $result->fetch_assoc();
                
                // Check if room has any occupied beds
                $occupied_beds = $room['number_of_beds'] - $room['remain'];
                if ($occupied_beds > 0) {
                    throw new Exception('Cannot delete room with occupied beds');
                }
                
                // Delete the room
                $stmt = $connection->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                
                if ($stmt->execute()) {
                    $response = [
                        'success' => true, 
                        'message' => 'Room deleted successfully',
                        'room_id' => $room_id
                    ];
                    // refresh the page
                    
                } else {
                    throw new Exception('Failed to delete room: ' . $connection->error);
                }
                break;

            case 'update_room_status':
                $room_id = $_POST['room_id'] ?? 0;
                $status = $_POST['status'] ?? '';
                
                if (empty($room_id) || empty($status)) {
                    throw new Exception('Room ID and Status are required');
                }
                
                // Validate status - only allow toggling between published and reserved
                $allowed_statuses = ['published', 'reserved'];
                if (!in_array($status, $allowed_statuses)) {
                    throw new Exception('Invalid status. Only published and reserved statuses are allowed.');
                }
                
                // Get current room status
                $stmt = $connection->prepare("SELECT status FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Room not found');
                }
                
                $room = $result->fetch_assoc();
                $current_status = $room['status'];
                
                // Toggle between published and reserved
                $new_status = ($current_status === 'published') ? 'reserved' : 'published';
                
                $updatedBy = $_SESSION['id'];
                $updatedAt = date('Y-m-d H:i:s');
                
                // Update room status
                $stmt = $connection->prepare("UPDATE rooms SET 
                                           status = ?, 
                                           updatedBy = ?, 
                                           updatedAt = ? 
                                           WHERE id = ?");
                $stmt->bind_param("sisi", $new_status, $updatedBy, $updatedAt, $room_id);
                
                if ($stmt->execute()) {
                    $response = [
                        'success' => true, 
                        'message' => 'Room status updated successfully',
                        'room' => [
                            'id' => $room_id,
                            'status' => $new_status,
                            'updatedAt' => $updatedAt
                        ]
                    ];
                } else {
                    throw new Exception('Failed to update room status: ' . $connection->error);
                }
                break;

            case 'update_all_rooms_status':
                if (empty($hostel_id)) {
                    throw new Exception('Hostel ID is required');
                }
                
                $new_status = $_POST['status'] ?? '';
                if (!in_array($new_status, ['published', 'reserved'])) {
                    throw new Exception('Invalid status. Status must be either "published" or "reserved"');
                }
                
                $updatedBy = $_SESSION['id'];
                $updatedAt = date('Y-m-d H:i:s');
                
                // Update all rooms in this hostel to the new status
                $stmt = $connection->prepare("UPDATE rooms SET status = ?, updatedBy = ?, updatedAt = ? WHERE hostel_id = ?");
                $stmt->bind_param("sisi", $new_status, $updatedBy, $updatedAt, $hostel_id);
                
                if ($stmt->execute()) {
                    $response = [
                        'success' => true,
                        'message' => 'All rooms have been updated successfully',
                        'updated_count' => $stmt->affected_rows
                    ];
                } else {
                    throw new Exception('Failed to update rooms: ' . $connection->error);
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
        if (!isset($response)) {
            $response = ['success' => false, 'message' => 'Invalid action'];
        }
        
        // Ensure response is valid JSON
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        $jsonResponse = json_encode($response);
        if ($jsonResponse === false) {
            // If json_encode fails, send a simple error response
            $response = [
                'success' => false,
                'message' => 'Error encoding response',
                'json_error' => json_last_error_msg()
            ];
            $jsonResponse = json_encode($response);
        }
        
        echo $jsonResponse;
        
    } catch (Exception $e) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(400);
        }
        
        $errorResponse = [
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        
        // Only include trace in development
        if (ini_get('display_errors')) {
            $errorResponse['trace'] = $e->getTraceAsString();
        }
        
        echo json_encode($errorResponse);
    }
    
    // Ensure no further output is sent
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    exit();
}

// Get hostel details
$hostel_query = mysqli_query($connection, "SELECT h.*, c.name as campus_name 
                                         FROM hostels h 
                                         LEFT JOIN campuses c ON h.campus_id = c.id 
                                         WHERE h.id = $hostel_id");
$hostel = mysqli_fetch_assoc($hostel_query);

if (!$hostel) {
    header("Location: manage_hostels.php");
    exit();
}

// Fetch rooms for this hostel with remaining beds count
$rooms_query = mysqli_query($connection, "
    SELECT *,
           remain as available_beds,
           (number_of_beds - remain) as assigned_beds
    FROM rooms 
    WHERE hostel_id = '$hostel_id'
    ORDER BY room_code
") or die(mysqli_error($connection));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - <?php echo htmlspecialchars($hostel['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #4154f1;
            color: white;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .btn-back {
            margin-bottom: 20px;
        }
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .badge {
            font-size: 0.85em;
            font-weight: 500;
            padding: 0.4em 0.8em;
        }
        .status-badge {
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }
        .status-published {
            background-color: #198754;
        }
        .status-reserved {
            background-color: #6c757d;
        }
        .status-maintenance {
            background-color: #fd7e14;
        }
        
        /* Loader Overlay */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loader-overlay .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <?php include("./includes/header.php"); ?>
    <?php include("./includes/menu.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Rooms in <?php echo htmlspecialchars($hostel['name']); ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="manage_hostels.php">Manage Hostels</a></li>
                    <li class="breadcrumb-item active">View Rooms</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-door-open me-2"></i>Manage Rooms - <?php echo htmlspecialchars($hostel['name']); ?>
                                    <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($hostel['campus_name']); ?></small>
                                </h5>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="updateAllRoomsStatus('reserved')">
                                        <i class="bi bi-bookmark-check me-1"></i> Reserve All
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="updateAllRoomsStatus('published')">
                                        <i class="bi bi-check-circle me-1"></i> Publish All
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="showAddRoomModal()">
                                        <i class="bi bi-plus-circle me-1"></i> Add Room
                                    </button>
                                    <a href="manage_hostels.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Hostels
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Room Code 1</th>
                                            <th>Room Code 2</th>
                                            <th>Beds</th>
                                            <th>Available</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($rooms_query) > 0): ?>
                                            <?php 
                                            while ($room = mysqli_fetch_assoc($rooms_query)): 
                                                $available_beds = max(0, $room['available_beds']);
                                                $assigned_beds = $room['assigned_beds'];
                                                $total_beds = $room['number_of_beds'];
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($room['room_code']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($room['room_code2']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?php echo $total_beds; ?> Beds
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 25px;">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: <?php echo ($available_beds / $total_beds) * 100; ?>%" 
                                                                aria-valuenow="<?php echo $available_beds; ?>" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="<?php echo $total_beds; ?>">
                                                                <?php echo $available_beds; ?> available
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge status-badge status-<?php echo $room['status']; ?>">
                                                            <?php echo ucfirst($room['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($room['createdAt'])); ?></td>
                                                    <td class="action-buttons">
                                                        <button class="btn btn-sm btn-info" 
                                                                onclick="viewRoomDetails(<?php echo htmlspecialchars(json_encode($room)); ?>)"
                                                                data-bs-toggle="tooltip" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)"
                                                                data-bs-toggle="tooltip" title="Edit Room">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="deleteRoom(<?php echo $room['id']; ?>)"
                                                                data-bs-toggle="tooltip" title="Delete Room">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <?php if($room['status'] === 'published'): ?>
                                                            <button class="btn btn-sm btn-warning" 
                                                                    onclick="updateRoomStatus(<?php echo $room['id']; ?>, 'reserved')"
                                                                    data-bs-toggle="tooltip" title="Set as Reserved">
                                                                <i class="bi bi-bookmark-check"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-success" 
                                                                    onclick="updateRoomStatus(<?php echo $room['id']; ?>, 'published')"
                                                                    data-bs-toggle="tooltip" title="Publish">
                                                                <i class="bi bi-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                       
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No rooms found for this hostel.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Loader Overlay -->
    <div id="loader" class="loader-overlay" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Add/Edit Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <input type="hidden" id="room_id" name="room_id">
                        <input type="hidden" id="hostel_id" name="hostel_id" value="<?php echo $hostel_id; ?>">
                        <input type="hidden" id="action" name="action" value="add_room">
                        
                        <div class="mb-3">
                            <label for="room_code" class="form-label">Room Code</label>
                            <input type="text" class="form-control" id="room_code" name="room_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_code2" class="form-label">Room Code 2</label>
                            <input type="text" class="form-control" id="room_code2" name="room_code2" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="number_of_beds" class="form-label">Number of Beds</label>
                            <input type="number" class="form-control" id="number_of_beds" name="number_of_beds" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRoom()">Save Room</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Details Modal -->
    <div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-labelledby="roomDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomDetailsModalLabel">Room Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="roomDetailsContent">
                    <!-- Room details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Global variables
        let currentHostelId = <?php echo $hostel_id; ?>;
        let roomsData = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Load rooms on page load
            loadRooms();
            
            // Handle form submission
            const roomForm = document.getElementById('roomForm');
            if (roomForm) {
                roomForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveRoom();
                });
            }
        });
        
        // Show/hide loader functions
        function showLoader(show = true) {
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = show ? 'flex' : 'none';
            }
        }
        
        function hideLoader() {
            showLoader(false);
        }

        // Load all rooms for the current hostel
        function loadRooms() {
            showLoader(true);
            
            fetch(`view_rooms.php?action=get_rooms&hostel_id=${currentHostelId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    roomsData = data.rooms;
                    renderRoomsTable(roomsData);
                } else {
                    showError(data.message || 'Failed to load rooms');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while loading rooms');
            })
            .finally(() => {
                showLoader(false);
            });
        }

        // Show add room modal
        function showAddRoomModal() {
            const form = document.getElementById('roomForm');
            form.reset();
            form.dataset.action = 'add_room';
            form.querySelector('input[name="room_id"]').value = '';
            document.getElementById('roomModalLabel').textContent = 'Add New Room';
            
            const modal = new bootstrap.Modal(document.getElementById('roomModal'));
            modal.show();
        }

        // Edit room
        function editRoom(room) {
            document.getElementById('room_id').value = room.id;
            document.getElementById('room_code').value = room.room_code;
            document.getElementById('room_code2').value = room.room_code2;
            document.getElementById('number_of_beds').value = room.number_of_beds;
            document.getElementById('action').value = 'edit_room';
            document.getElementById('roomModalLabel').textContent = 'Edit Room';
            
            const modal = new bootstrap.Modal(document.getElementById('roomModal'));
            modal.show();
        }

        // Save room (add or edit)
        function saveRoom() {
            const form = document.getElementById('roomForm');
            const formData = new FormData(form);
            const action = form.dataset.action || 'add_room';
            
            showLoader(true);
            
            fetch('view_rooms.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
                    modal.hide();
                    
                    // Show success message and reload page
                    showSuccess(data.message || 'Operation completed successfully', true);
                    
                    // Reload the page after a short delay to show the success message
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showError(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while processing your request');
            })
            .finally(() => {
                showLoader(false);
            });
        }

        // Delete room
        function deleteRoom(roomId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('action', 'delete_room');
                    formData.append('room_id', roomId);
                    formData.append('hostel_id', currentHostelId);
                    
                    return fetch('view_rooms.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to delete room');
                        }
                        return data;
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    showSuccess('Room has been deleted', true);
                    // Page will reload automatically from showSuccess
                }
            }).catch(error => {
                showError(error.message || 'An error occurred while deleting the room');
            });
        }

        // Show/hide loader functions
        function showLoader(show = true) {
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = show ? 'flex' : 'none';
            }
        }

        function hideLoader() {
            showLoader(false);
        }

        // Toggle room status between reserved and published
        function updateRoomStatus(roomId, newStatus) {
            showLoader();
            
            fetch('view_rooms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=update_room_status&room_id=${roomId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    // Update the UI directly without page refresh
                    const roomRow = document.querySelector(`tr[data-room-id="${roomId}"]`);
                    if (roomRow) {
                        const statusBadge = roomRow.querySelector('.status-badge');
                        const reserveBtn = roomRow.querySelector('.reserve-btn');
                        if (statusBadge) {
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            statusBadge.className = `badge status-badge status-${newStatus}`;
                        }
                        if (reserveBtn) {
                            reserveBtn.className = `btn btn-sm ${newStatus === 'reserved' ? 'btn-success' : 'btn-warning'}`;
                            reserveBtn.innerHTML = newStatus === 'reserved' ? '<i class="bi bi-check-circle"></i>' : '<i class="bi bi-bookmark-check"></i>';
                            reserveBtn.title = newStatus === 'reserved' ? 'Mark as Available' : 'Mark as Reserved';
                            // Update the onclick handler to toggle the status
                            reserveBtn.onclick = (e) => {
                                e.stopPropagation();
                                updateRoomStatus(roomId, newStatus === 'reserved' ? 'published' : 'reserved');
                            };
                        }
                    }
                    showSuccess(`Room marked as ${newStatus} successfully`, true);
                    // Reload the page after a short delay to show the success message
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Failed to update room status');
                }
            })
            .catch(error => {
                hideLoader();
                console.error('Error:', error);
                showError(error.message || 'Failed to update room status');
            });
        }

        // Reserve room
        function reserveRoom(roomId) {
            const room = roomsData.find(r => r.id == roomId);
            if (!room) return;
            
            Swal.fire({
                title: 'Reserve Bed',
                html: `
                    <div class="text-start">
                        <p>Room: <strong>${room.room_code}</strong></p>
                        <p>Available Beds: <strong>${room.remain} of ${room.number_of_beds}</strong></p>
                        <div class="mb-3">
                            <label for="studentId" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="studentId" placeholder="Enter student ID">
                        </div>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Reserve',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const studentId = document.getElementById('studentId').value.trim();
                    if (!studentId) {
                        Swal.showValidationMessage('Please enter student ID');
                        return false;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'reserve_room');
                    formData.append('room_id', roomId);
                    formData.append('student_id', studentId);
                    formData.append('hostel_id', currentHostelId);
                    
                    return fetch('view_rooms.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to reserve bed');
                        }
                        return data;
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    showSuccess('Bed reserved successfully', true);
                }
            }).catch(error => {
                showError(error.message || 'An error occurred while reserving the bed');
            });
        }

        // View room details
        function viewRoomDetails(room) {
            const modal = new bootstrap.Modal(document.getElementById('roomDetailsModal'));
            const modalBody = document.getElementById('roomDetailsContent');
            
            // Calculate usage percentage
            const totalBeds = parseInt(room.number_of_beds);
            const availableBeds = parseInt(room.remain);
            const usedBeds = totalBeds - availableBeds;
            const usagePercentage = totalBeds > 0 ? (usedBeds / totalBeds) * 100 : 0;
            
            // Format the room details
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Room Code:</strong> ${room.room_code}</p>
                        <p><strong>Room Code 2:</strong> ${room.room_code2}</p>
                        <p><strong>Total Beds:</strong> ${totalBeds}</p>
                        <p><strong>Available Beds:</strong> ${availableBeds}</p>
                        <p><strong>Occupied Beds:</strong> ${usedBeds}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge status-badge status-${room.status}">
                                ${room.status.charAt(0).toUpperCase() + room.status.slice(1)}
                            </span>
                        </p>
                        <p><strong>Created At:</strong> ${new Date(room.createdAt).toLocaleDateString()}</p>
                        <div class="mt-3">
                            <p><strong>Occupancy:</strong></p>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                    style="width: ${usagePercentage}%" 
                                    aria-valuenow="${usagePercentage}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    ${usagePercentage.toFixed(1)}%
                                </div>
                            </div>
                            <small class="text-muted">${usedBeds} of ${totalBeds} beds in use</small>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                   
                </div>
            `;
            
            modalBody.innerHTML = details;
            modal.show();
        }
        // Helper function to render rooms table
        function renderRoomsTable(rooms) {
            const tbody = document.querySelector('#roomsTable tbody');
            if (!tbody) return;
            
            if (rooms.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No rooms found</td></tr>';
                return;
            }
            
            tbody.innerHTML = rooms.map(room => {
                const totalBeds = parseInt(room.number_of_beds);
                const availableBeds = parseInt(room.remain);
                const usedBeds = totalBeds - availableBeds;
                const usagePercentage = totalBeds > 0 ? Math.round((usedBeds / totalBeds) * 100) : 0;
                
                return `
                    <tr data-room-id="${room.id}">
                        <td><strong>${escapeHtml(room.room_code)}</strong></td>
                        <td>
                            <span class="badge bg-primary">${totalBeds} Beds</span>
                        </td>
                        <td>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                    style="width: ${usagePercentage}%" 
                                    aria-valuenow="${usagePercentage}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    ${availableBeds} available
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge status-badge status-${room.status}">
                                ${room.status.charAt(0).toUpperCase() + room.status.slice(1)}
                            </span>
                        </td>
                        <td>${formatDate(room.createdAt)}</td>
                        <td class="action-buttons">
                            <button class="btn btn-sm btn-info" 
                                    onclick="viewRoomDetails(${escapeHtml(JSON.stringify(room))})"
                                    data-bs-toggle="tooltip" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" 
                                    onclick="editRoom(${escapeHtml(JSON.stringify(room))})"
                                    data-bs-toggle="tooltip" title="Edit Room">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm ${room.status === 'reserved' ? 'btn-success' : 'btn-warning'} reserve-btn" 
                                    onclick="updateRoomStatus(${room.id}, '${room.status === 'reserved' ? 'published' : 'reserved'})"
                                    data-bs-toggle="tooltip" 
                                    title="${room.status === 'reserved' ? 'Mark as Available' : 'Mark as Reserved'}">
                                <i class="bi ${room.status === 'reserved' ? 'bi-check-circle' : 'bi-bookmark-check'}"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="deleteRoom(${room.id})"
                                    data-bs-toggle="tooltip" 
                                    title="Delete Room">
                                <i class="bi bi-trash"></i>
                            </button>
                            ${room.status === 'published' ? `
                                <button class="btn btn-sm btn-warning" 
                                        onclick="updateRoomStatus(${room.id}, 'reserved')"
                                        data-bs-toggle="tooltip" title="Set as Reserved">
                                    <i class="bi bi-bookmark-check"></i>
                                </button>` : ''}
                            ${room.status !== 'published' ? `
                                <button class="btn btn-sm btn-success" 
                                        onclick="updateRoomStatus(${room.id}, 'published')"
                                        data-bs-toggle="tooltip" title="Publish">
                                    <i class="bi bi-check-circle"></i>
                                </button>` : ''}
                            ${availableBeds > 0 ? `
                                <button class="btn btn-sm btn-primary" 
                                        onclick="reserveRoom(${room.id})"
                                        data-bs-toggle="tooltip" title="Reserve Bed">
                                    <i class="bi bi-person-plus"></i> Reserve
                                </button>` : ''}
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Reinitialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }
        
        // Helper function to show success message
        function showSuccess(message, reload = false) {
            Swal.fire({
                title: 'Success!',
                text: message,
                icon: 'success',
                confirmButtonText: 'OK',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                if (reload) {
                    window.location.reload();
                }
            });
        }
        
        // Helper function to show error message
        function showError(message) {
            Swal.fire({
                title: 'Error!',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
        
        // Helper function to show/hide loader
        function showLoader(show) {
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = show ? 'flex' : 'none';
            }
        }
        
        // Helper function to escape HTML
        function escapeHtml(unsafe) {
            return String(unsafe)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        // Update status for all rooms
        function updateAllRoomsStatus(newStatus) {
            const action = newStatus === 'published' ? 'publish' : 'reserve';
            const statusText = newStatus === 'published' ? 'publish' : 'reserve';
            
            Swal.fire({
                title: `Are you sure?`,
                text: `This will ${statusText} ALL rooms in this hostel. This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'published' ? '#198754' : '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${statusText} all!`,
                cancelButtonText: 'Cancel',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('action', 'update_all_rooms_status');
                    formData.append('status', newStatus);
                    formData.append('hostel_id', currentHostelId);
                    
                    return fetch('view_rooms.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || `Failed to ${statusText} all rooms`);
                        }
                        return data;
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    showSuccess(`All rooms have been ${statusText}d successfully!`, true);
                }
            }).catch(error => {
                showError(error.message || `An error occurred while trying to ${statusText} all rooms`);
            });
        }
        
        // Reserve a room
        function reserveRoom(roomId) {
            const room = roomsData.find(r => r.id == roomId);
            if (!room) return;
            
            Swal.fire({
                title: 'Reserve Bed',
                html: `
                    <div class="text-start">
                        <p>You are about to reserve a bed in <strong>${escapeHtml(room.room_code)}</strong>.</p>
                        <p>Available beds: <strong>${room.remain || 0}</strong></p>
                        <div class="mb-3">
                            <label for="studentId" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="studentId" placeholder="Enter student ID" required>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Reserve',
                cancelButtonText: 'Cancel',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const studentId = document.getElementById('studentId').value.trim();
                    if (!studentId) {
                        Swal.showValidationMessage('Please enter a student ID');
                        return false;
                    }
                    
                    return fetch('view_rooms.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=reserve_room&room_id=${roomId}&student_id=${encodeURIComponent(studentId)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to reserve bed');
                        }
                        return data;
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            })
            .then((result) => {
                if (result.isConfirmed) {
                    showSuccess('Bed reserved successfully');
                    loadRooms(); // Refresh the rooms list
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError(error.message || 'Failed to reserve bed');
            });
        }
    </script>
</body>
</html>
