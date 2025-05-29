<?php
include('connection.php');
// session_start();
$id =$_SESSION['id'];
$sql = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($connection, $sql);
$row = mysqli_fetch_assoc($result);
$mycampus = $row['campus'];
$role=$row['role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Set JSON content type header
        header('Content-Type: application/json');
        
        $response = ['success' => false, 'message' => ''];
        
        try {
            switch ($_POST['action']) {
                case 'add_campus':
                case 'edit_campus':
                    $name = mysqli_real_escape_string($connection, $_POST['campus_name']);
                    $id = isset($_POST['campus_id']) ? (int)$_POST['campus_id'] : 0;
                    
                    // Check if campus name already exists
                    $check_query = "SELECT id FROM campuses WHERE name = '$name' AND id != $id";
                    $result = mysqli_query($connection, $check_query);
                    
                    if (!$result) {
                        throw new Exception("Database error: " . mysqli_error($connection));
                    }
                    
                    if (mysqli_num_rows($result) > 0) {
                        $response['message'] = 'A campus with this name already exists.';
                    } else {
                        if ($_POST['action'] === 'add_campus') {
                            if (!mysqli_query($connection, "INSERT INTO campuses (name) VALUES ('$name')")) {
                                throw new Exception("Failed to add campus: " . mysqli_error($connection));
                            }
                            $response['message'] = 'Campus added successfully.';
                        } else {
                            if (!mysqli_query($connection, "UPDATE campuses SET name = '$name' WHERE id = $id")) {
                                throw new Exception("Failed to update campus: " . mysqli_error($connection));
                            }
                            $response['message'] = 'Campus updated successfully.';
                        }
                        $response['success'] = true;
                    }
                    break;
                
                case 'add_hostel':
                case 'edit_hostel':
                    $name = mysqli_real_escape_string($connection, $_POST['hostel_name']);
                    $campus_id = (int)$_POST['campus_id'];
                    $id = isset($_POST['hostel_id']) ? (int)$_POST['hostel_id'] : 0;
                    
                    // Check if hostel name already exists in the same campus
                    $check_query = "SELECT id FROM hostels WHERE name = '$name' AND campus_id = $campus_id AND id != $id";
                    $result = mysqli_query($connection, $check_query);
                    
                    if (!$result) {
                        throw new Exception("Database error: " . mysqli_error($connection));
                    }
                    
                    if (mysqli_num_rows($result) > 0) {
                        $response['message'] = 'A hostel with this name already exists in this campus.';
                    } else {
                        if ($_POST['action'] === 'add_hostel') {
                            if (!mysqli_query($connection, "INSERT INTO hostels (name, campus_id) VALUES ('$name', $campus_id)")) {
                                throw new Exception("Failed to add hostel: " . mysqli_error($connection));
                            }
                            $response['message'] = 'Hostel added successfully.';
                        } else {
                            if (!mysqli_query($connection, "UPDATE hostels SET name = '$name', campus_id = $campus_id WHERE id = $id")) {
                                throw new Exception("Failed to update hostel: " . mysqli_error($connection));
                            }
                            $response['message'] = 'Hostel updated successfully.';
                        }
                        $response['success'] = true;
                    }
                    break;
                
                case 'add_room':
                case 'edit_room':
                    $room_code = mysqli_real_escape_string($connection, $_POST['room_code']);
                    $number_of_beds = (int)$_POST['number_of_beds'];
                    $hostel_id = (int)$_POST['hostel_id'];
                    $id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
                    
                    // Check if room code already exists in the same hostel
                    $check_query = "SELECT id FROM rooms WHERE room_code = '$room_code' AND hostel_id = $hostel_id AND id != $id";
                    $result = mysqli_query($connection, $check_query);
                    
                    if (!$result) {
                        throw new Exception("Database error: " . mysqli_error($connection));
                    }
                    
                    if (mysqli_num_rows($result) > 0) {
                        $response['message'] = 'A room with this code already exists in this hostel.';
                    } else {
                        if ($_POST['action'] === 'add_room') {
                            if (!mysqli_query($connection, "INSERT INTO rooms (room_code, number_of_beds, hostel_id, remain) VALUES ('$room_code', $number_of_beds, $hostel_id, $number_of_beds)")) {
                                throw new Exception("Failed to add room: " . mysqli_error($connection));
                            }
                            $response['message'] = 'Room added successfully.';
                        } else {
                            // Get current number of beds and remaining beds
                            $current_room = mysqli_fetch_assoc(mysqli_query($connection, "SELECT number_of_beds, remain FROM rooms WHERE id = $id"));
                            if (!$current_room) {
                                throw new Exception("Room not found");
                            }
                            $current_beds = $current_room['number_of_beds'];
                            $current_remain = $current_room['remain'];
                            
                            // Calculate new remaining beds
                            $bed_difference = $number_of_beds - $current_beds;
                            $new_remain = $current_remain + $bed_difference;
                            
                            // Ensure remaining beds doesn't go below 0
                            $new_remain = max(0, $new_remain);
                            
                            if (!mysqli_query($connection, "UPDATE rooms SET room_code = '$room_code', number_of_beds = $number_of_beds, remain = $new_remain WHERE id = $id")) {
                                throw new Exception("Failed to update room: " . mysqli_error($connection));
                            }
                            $response['message'] = 'Room updated successfully.';
                        }
                        $response['success'] = true;
                    }
                    break;
                
                case 'delete_campus':
                    $id = (int)$_POST['campus_id'];
                    if (!mysqli_query($connection, "DELETE FROM campuses WHERE id = $id")) {
                        throw new Exception("Failed to delete campus: " . mysqli_error($connection));
                    }
                    $response = ['success' => true, 'message' => 'Campus deleted successfully.'];
                    break;
                
                case 'delete_hostel':
                    $id = (int)$_POST['hostel_id'];
                    if (!mysqli_query($connection, "DELETE FROM hostels WHERE id = $id")) {
                        throw new Exception("Failed to delete hostel: " . mysqli_error($connection));
                    }
                    $response = ['success' => true, 'message' => 'Hostel deleted successfully.'];
                    break;
                
                case 'delete_room':
                    $id = (int)$_POST['room_id'];
                    if (!mysqli_query($connection, "DELETE FROM rooms WHERE id = $id")) {
                        throw new Exception("Failed to delete room: " . mysqli_error($connection));
                    }
                    $response = ['success' => true, 'message' => 'Room deleted successfully.'];
                    break;
                
                default:
                    throw new Exception("Invalid action specified");
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
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
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UR-HOSTELS</title>
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
                                        <?php while ($campus = mysqli_fetch_assoc($campuses_query)): ?>
                                        <tr>
                                            <td><?php echo $campus['id']; ?></td>
                                            <td><?php echo htmlspecialchars($campus['name']); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-primary" onclick="editCampus(<?php echo $campus['id']; ?>, '<?php echo htmlspecialchars($campus['name']); ?>')">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <?php if($role !== 'warefare'): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCampus(<?php echo $campus['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-info" onclick="showHostels(<?php echo $campus['id']; ?>)">
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
                                <button type="button" class="btn btn-primary" onclick="showAddHostelModal(currentCampusId)">
                                    <i class="bi bi-plus-circle me-1"></i>Add Hostel
                                </button>
                            </div>
                            
                            <div id="hostelsTable" class="table-responsive">
                                <!-- Hostels will be loaded here dynamically -->
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
                                    <button type="button" class="btn btn-primary" onclick="showAddRoomModal(currentHostelId)">
                                        <i class="bi bi-plus-circle me-1"></i>Add Room
                                    </button>
                                </div>
                            </div>
                            
                            <div id="roomsTable" class="table-responsive">
                                <!-- Rooms will be loaded here dynamically -->
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    Showing <span id="roomStart">0</span> to <span id="roomEnd">0</span> of <span id="roomTotal">0</span> rooms
                                </div>
                                <nav aria-label="Room pagination">
                                    <ul class="pagination mb-0" id="roomPagination">
                                        <!-- Pagination will be loaded here dynamically -->
                                    </ul>
                                </nav>
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

    <!-- Add Hostel Modal -->
    <div class="modal fade" id="addHostelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-house-door me-2"></i>Add Hostel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_hostel">
                        <input type="hidden" name="hostel_id" value="">
                        <input type="hidden" name="campus_id" value="">
                        <div class="mb-3">
                            <label class="form-label">Hostel Name</label>
                            <input type="text" class="form-control" name="hostel_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Save Hostel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-door-open me-2"></i>Add Room
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_room">
                        <input type="hidden" name="room_id" value="">
                        <input type="hidden" name="hostel_id" value="">
                        <div class="mb-3">
                            <label class="form-label">Room Code</label>
                            <input type="text" class="form-control" name="room_code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Number of Beds</label>
                            <input type="number" class="form-control" name="number_of_beds" required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Save Room
                        </button>
                    </div>
                </form>
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

    <!-- Include your existing JS files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        let currentCampusId = null;
        let currentHostelId = null;
        let currentPage = 1;
        let searchQuery = '';
        const roomsPerPage = 10;

        // Function to show hostels for a campus
        function showHostels(campusId) {
            currentCampusId = campusId;
            
            // Get campus name for the title
            fetch(`get_campus_name.php?campus_id=${campusId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.querySelector('#hostelsSection .card-title').innerHTML = 
                            `<i class="bi bi-house-door me-2"></i>Hostels in ${data.campus_name}`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching campus name:', error);
                    document.querySelector('#hostelsSection .card-title').innerHTML = 
                        `<i class="bi bi-house-door me-2"></i>Hostels`;
                });
            
            fetch(`get_hostels.php?campus_id=${campusId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('hostelsTable').innerHTML = html;
                    document.getElementById('hostelsSection').style.display = 'block';
                    document.getElementById('roomsSection').style.display = 'none';
                })
                .catch(error => {
                    console.error('Error fetching hostels:', error);
                    document.getElementById('hostelsTable').innerHTML = 
                        '<div class="alert alert-danger">Error loading hostels. Please try again.</div>';
                });
        }

        // Function to show rooms for a hostel with pagination
        function showRooms(hostelId, page = 1, search = '') {
            currentHostelId = hostelId;
            currentPage = page;
            searchQuery = search;
            
            // Get hostel name for the title
            fetch(`get_hostel_name.php?hostel_id=${hostelId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.querySelector('#roomsSection .card-title').innerHTML = 
                            `<i class="bi bi-door-open me-2"></i>Rooms in ${data.hostel_name}`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching hostel name:', error);
                    document.querySelector('#roomsSection .card-title').innerHTML = 
                        `<i class="bi bi-door-open me-2"></i>Rooms`;
                });
            
            fetch(`get_rooms.php?hostel_id=${hostelId}&page=${page}&search=${encodeURIComponent(search)}&per_page=${roomsPerPage}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('roomsTable').innerHTML = html;
                    document.getElementById('roomsSection').style.display = 'block';
                    updatePagination();
                })
                .catch(error => {
                    console.error('Error fetching rooms:', error);
                    document.getElementById('roomsTable').innerHTML = 
                        '<div class="alert alert-danger">Error loading rooms. Please try again.</div>';
                });
        }

        // Function to update pagination
        function updatePagination() {
            fetch(`get_rooms_count.php?hostel_id=${currentHostelId}&search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    const totalRooms = data.total;
                    const totalPages = Math.ceil(totalRooms / roomsPerPage);
                    
                    // Update room count display
                    document.getElementById('roomStart').textContent = ((currentPage - 1) * roomsPerPage) + 1;
                    document.getElementById('roomEnd').textContent = Math.min(currentPage * roomsPerPage, totalRooms);
                    document.getElementById('roomTotal').textContent = totalRooms;
                    
                    // Generate pagination HTML
                    let paginationHtml = '';
                    
                    // Previous button
                    paginationHtml += `
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="return changePage(${currentPage - 1})" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    `;
                    
                    // Page numbers
                    for (let i = 1; i <= totalPages; i++) {
                        if (
                            i === 1 || // First page
                            i === totalPages || // Last page
                            (i >= currentPage - 2 && i <= currentPage + 2) // Pages around current page
                        ) {
                            paginationHtml += `
                                <li class="page-item ${i === currentPage ? 'active' : ''}">
                                    <a class="page-link" href="#" onclick="return changePage(${i})">${i}</a>
                                </li>
                            `;
                        } else if (
                            i === currentPage - 3 || // Before current page range
                            i === currentPage + 3 // After current page range
                        ) {
                            paginationHtml += `
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            `;
                        }
                    }
                    
                    // Next button
                    paginationHtml += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="return changePage(${currentPage + 1})" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    `;
                    
                    document.getElementById('roomPagination').innerHTML = paginationHtml;
                });
        }

        // Function to change page
        function changePage(page) {
            if (page < 1) return false;
            showRooms(currentHostelId, page, searchQuery);
            return false;
        }

        // Function to search rooms
        function searchRooms() {
            const searchInput = document.getElementById('roomSearch');
            showRooms(currentHostelId, 1, searchInput.value);
        }

        // Add event listener for search input
        document.getElementById('roomSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchRooms();
            }
        });

        // Generic function to handle form submissions
        async function handleFormSubmit(form, modalId) {
            try {
                const formData = new FormData(form);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new TypeError("Oops, we haven't got JSON!");
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    modal.hide();
                    
                    // Refresh the current view
                    if (currentHostelId) {
                        showRooms(currentHostelId);
                    } else if (currentCampusId) {
                        showHostels(currentCampusId);
                    } else {
                        location.reload(); // Only reload if we're at the top level
                    }
                    
                    // Show success message
                    showAlert('Success!', result.message, 'success');
                } else {
                    // Show error message
                    showAlert('Error!', result.message || 'An error occurred', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error!', 'An error occurred while processing your request. Please try again.', 'danger');
            }
        }

        // Function to show add room modal
        function showAddRoomModal(hostelId) {
            const modal = new bootstrap.Modal(document.getElementById('addRoomModal'));
            const form = document.querySelector('#addRoomModal form');
            form.querySelector('[name="action"]').value = 'add_room';
            form.querySelector('[name="hostel_id"]').value = hostelId;
            form.querySelector('[name="room_id"]').value = '';
            form.reset();
            document.querySelector('#addRoomModal .modal-title').textContent = 'Add Room';
            document.querySelector('#addRoomModal button[type="submit"]').textContent = 'Add Room';
            modal.show();
        }

        // Function to edit campus
        function editCampus(id, name) {
            const modal = new bootstrap.Modal(document.getElementById('addCampusModal'));
            const form = document.querySelector('#addCampusModal form');
            form.querySelector('[name="action"]').value = 'edit_campus';
            form.querySelector('[name="campus_id"]').value = id;
            form.querySelector('[name="campus_name"]').value = name;
            document.querySelector('#addCampusModal .modal-title').textContent = 'Edit Campus';
            document.querySelector('#addCampusModal button[type="submit"]').textContent = 'Update Campus';
            modal.show();
        }

        // Function to delete campus
        async function deleteCampus(id) {
            if (confirm('Are you sure you want to delete this campus? This will also delete all associated hostels and rooms.')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_campus');
                    formData.append('campus_id', id);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        location.reload();
                        showAlert('Success!', result.message, 'success');
                    } else {
                        showAlert('Error!', result.message, 'danger');
                    }
                } catch (error) {
                    showAlert('Error!', 'Failed to delete campus.', 'danger');
                }
            }
        }

        // Function to show add hostel modal
        function showAddHostelModal(campusId) {
            const modal = new bootstrap.Modal(document.getElementById('addHostelModal'));
            const form = document.querySelector('#addHostelModal form');
            form.querySelector('[name="action"]').value = 'add_hostel';
            form.querySelector('[name="campus_id"]').value = campusId;
            form.querySelector('[name="hostel_id"]').value = '';
            form.reset();
            document.querySelector('#addHostelModal .modal-title').textContent = 'Add Hostel';
            document.querySelector('#addHostelModal button[type="submit"]').textContent = 'Add Hostel';
            modal.show();
        }

        // Function to edit hostel
        function editHostel(id, name, campusId) {
            const modal = new bootstrap.Modal(document.getElementById('addHostelModal'));
            const form = document.querySelector('#addHostelModal form');
            form.querySelector('[name="action"]').value = 'edit_hostel';
            form.querySelector('[name="hostel_id"]').value = id;
            form.querySelector('[name="campus_id"]').value = campusId;
            form.querySelector('[name="hostel_name"]').value = name;
            document.querySelector('#addHostelModal .modal-title').textContent = 'Edit Hostel';
            document.querySelector('#addHostelModal button[type="submit"]').textContent = 'Update Hostel';
            modal.show();
        }

        // Function to delete hostel
        async function deleteHostel(id) {
            if (confirm('Are you sure you want to delete this hostel? This will also delete all associated rooms.')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_hostel');
                    formData.append('hostel_id', id);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showHostels(currentCampusId);
                        showAlert('Success!', result.message, 'success');
                    } else {
                        showAlert('Error!', result.message, 'danger');
                    }
                } catch (error) {
                    showAlert('Error!', 'Failed to delete hostel.', 'danger');
                }
            }
        }

        // Function to edit room
        function editRoom(id, roomCode, numberOfBeds, hostelId) {
            const modal = new bootstrap.Modal(document.getElementById('addRoomModal'));
            const form = document.querySelector('#addRoomModal form');
            form.querySelector('[name="action"]').value = 'edit_room';
            form.querySelector('[name="room_id"]').value = id;
            form.querySelector('[name="hostel_id"]').value = hostelId;
            form.querySelector('[name="room_code"]').value = roomCode;
            form.querySelector('[name="number_of_beds"]').value = numberOfBeds;
            
            document.querySelector('#addRoomModal .modal-title').textContent = 'Edit Room';
            document.querySelector('#addRoomModal button[type="submit"]').textContent = 'Update Room';
            modal.show();
        }

        // Function to delete room
        async function deleteRoom(id) {
            if (confirm('Are you sure you want to delete this room?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_room');
                    formData.append('room_id', id);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showRooms(currentHostelId);
                        showAlert('Success!', result.message, 'success');
                    } else {
                        showAlert('Error!', result.message, 'danger');
                    }
                } catch (error) {
                    showAlert('Error!', 'Failed to delete room.', 'danger');
                }
            }
        }

        // Function to show alert messages
        function showAlert(title, message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <strong>${title}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto dismiss after 3 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Add event listeners to all forms
        document.addEventListener('DOMContentLoaded', function() {
            // Campus form
            document.querySelector('#addCampusModal form').addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'addCampusModal');
            });

            // Hostel form
            document.querySelector('#addHostelModal form').addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'addHostelModal');
            });

            // Room form
            document.querySelector('#addRoomModal form').addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'addRoomModal');
            });
        });
    </script>
</body>
</html> 