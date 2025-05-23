<?php
session_start();
include("connection.php");

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get student information from session
$student_id = $_SESSION['student_id'];
$student_campus = $_SESSION['student_campus'];
$student_gender = $_SESSION['student_gender'];
$student_year = $_SESSION['student_year'];
$student_regnumber = $_SESSION['student_regnumber'];

// Check if student has an active application
$application_query = "SELECT a.*, r.room_code, h.name as hostel_name, a.slep
                     FROM applications a
                     JOIN rooms r ON a.room_id = r.id
                     JOIN hostels h ON r.hostel_id = h.id
                     WHERE a.regnumber = ? AND a.status != 'rejected'";
$app_stmt = $connection->prepare($application_query);
$app_stmt->bind_param("s", $student_regnumber);
$app_stmt->execute();
$current_application = $app_stmt->get_result()->fetch_assoc();

// Get available hostels for student's campus
$hostel_query = "SELECT h.*, c.name as campus_name 
                FROM hostels h 
                JOIN campuses c ON h.campus_id = c.id 
                WHERE c.name = ?";
$stmt = $connection->prepare($hostel_query);
$stmt->bind_param("s", $student_campus);
$stmt->execute();
$hostels = $stmt->get_result();

// Function to check hostel eligibility
function checkHostelEligibility($connection, $hostel_id, $student_gender, $student_year) {
    $attributes_query = "SELECT * FROM hostel_attributes WHERE hostel_id = ?";
    $attributes_stmt = $connection->prepare($attributes_query);
    $attributes_stmt->bind_param("i", $hostel_id);
    $attributes_stmt->execute();
    $attributes = $attributes_stmt->get_result();

    $is_eligible = true;
    while ($attr = $attributes->fetch_assoc()) {
        if ($attr['attribute_key'] === 'gender' && $attr['attribute_value'] !== $student_gender) {
            $is_eligible = false;
            break;
        }
        if ($attr['attribute_key'] === 'year_of_study' && $attr['attribute_value'] != $student_year) {
            $is_eligible = false;
            break;
        }
    }
    return $is_eligible;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Select Hostel - UR-HUYE</title>
    <link href="../icon1.png" rel="icon" type="image/x-icon">
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Nunito|Poppins" rel="stylesheet">
    <link href="../Dashboard/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Dashboard/assets/css/style.css" rel="stylesheet">
    <style>
        .hostel-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
            height: 100%;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hostel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .room-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }
        .carousel-item {
            padding: 20px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .hostel-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        .room-badge {
            font-size: 0.9em;
            margin: 5px;
        }
        .carousel-control-prev, .carousel-control-next {
            width: 5%;
            background: rgba(0,0,0,0.1);
        }
        .room-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .room-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .carousel-indicators {
            margin-bottom: 0;
        }
        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 5px;
        }
        .hostel-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .hostel-nav button {
            margin: 0 5px;
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
        }
        .hostel-nav button.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .room-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-available {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-limited {
            background-color: #fff3cd;
            color: #856404;
        }
        .carousel-container {
            position: relative;
            padding: 20px 0;
        }
        .carousel-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #dee2e6, transparent);
        }
        .application-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        .roommate-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .receipt-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }
        .receipt-upload:hover {
            border-color: #0d6efd;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .hostel-pin {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            border-radius: 50px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 200px;
        }
        .hostel-pin:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .hostel-pin.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .hostel-pin.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #e9ecef;
        }
        .student-info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .student-info-card h4 {
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .student-info-item {
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .room-pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .room-pagination button {
            margin: 0 5px;
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .room-pagination button.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .room-pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .rooms-container {
            display: none;
        }
        .rooms-container.active {
            display: block;
        }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title text-center pb-0 fs-4">Hostel Application</h5>
                                    
                                    <?php if (isset($_SESSION['success_message'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?php 
                                            echo $_SESSION['success_message'];
                                            unset($_SESSION['success_message']);
                                            ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION['error_message'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php 
                                            echo $_SESSION['error_message'];
                                            unset($_SESSION['error_message']);
                                            ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Student Info Card -->
                                    <div class="student-info-card">
                                        <h4>Student Information</h4>
                                        <div class="student-info-item">
                                            <strong>Name:</strong> <?php echo $_SESSION['student_name']; ?>
                                        </div>
                                        <div class="student-info-item">
                                            <strong>Registration Number:</strong> <?php echo $_SESSION['student_regnumber']; ?>
                                        </div>
                                        <div class="student-info-item">
                                            <strong>Campus:</strong> <?php echo $_SESSION['student_campus']; ?>
                                        </div>
                                        <div class="student-info-item">
                                            <strong>College:</strong> <?php echo $_SESSION['student_college']; ?>
                                        </div>
                                        <div class="student-info-item">
                                            <strong>Program:</strong> <?php echo $_SESSION['student_program']; ?>
                                        </div>
                                        <div class="student-info-item">
                                            <strong>Year of Study:</strong> <?php echo $_SESSION['student_year']; ?>
                                        </div>
                                    </div>

                                    <!-- Hostel Pins -->
                                    <div class="text-center mb-4">
                                        <h4>Select a Hostel</h4>
                                        <div class="hostel-pins-container">
                                            <?php
                                            $hostels->data_seek(0);
                                            while ($hostel = $hostels->fetch_assoc()) {
                                                $is_eligible = checkHostelEligibility($connection, $hostel['id'], $student_gender, $student_year);
                                                $disabled_class = !$is_eligible ? 'disabled' : '';
                                                echo "<div class='hostel-pin {$disabled_class}' data-hostel-id='{$hostel['id']}'>";
                                                echo "<h5>{$hostel['name']}</h5>";
                                                if (!$is_eligible) {
                                                    echo "<small class='text-danger'>Not eligible</small>";
                                                }
                                                echo "</div>";
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Rooms Container -->
                                    <div class="rooms-container">
                                        <h4 class="text-center mb-4">Available Rooms</h4>
                                        <div class="room-list">
                                            <!-- Rooms will be loaded here via AJAX -->
                                        </div>
                                        <div class="room-pagination">
                                            <button class="prev-page" disabled>&laquo; Previous</button>
                                            <span class="page-info">Page <span class="current-page">1</span> of <span class="total-pages">1</span></span>
                                            <button class="next-page" disabled>Next &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Invoice Upload Section -->
                                    <div class="receipt-upload mt-4" style="display: none;">
                                        <h5>SLEP Payment Receipt</h5>
                                        <p class="text-muted mb-3">Please upload your bank payment receipt for RWF 40,000</p>
                                        
                                        <form action="upload_receipt.php" method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="application_id" id="application_id">
                                            <div class="mb-3">
                                                <label for="receipt" class="form-label">Upload Receipt</label>
                                                <input type="file" class="form-control" id="receipt" name="receipt" 
                                                       accept="image/*,.pdf" required>
                                                <small class="text-muted">Accepted formats: JPG, PNG, PDF (Max size: 2MB)</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                Upload Receipt
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Current Application Details -->
                                    <?php if ($current_application): ?>
                                    <div class="application-card mt-4">
                                        <h4>Your Current Application</h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($current_application['hostel_name']); ?></p>
                                                <p><strong>Room:</strong> <?php echo htmlspecialchars($current_application['room_code']); ?></p>
                                                <p><strong>Status:</strong> 
                                                    <span class="status-badge <?php echo $current_application['status'] === 'approved' ? 'status-approved' : 'status-pending'; ?>">
                                                        <?php echo ucfirst($current_application['status']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Hostel Fees:</strong> RWF 40,000</p>
                                                <p><strong>Application Date:</strong> <?php echo date('F j, Y', strtotime($current_application['created_at'])); ?></p>
                                            </div>
                                        </div>

                                        <?php if ($current_application['slep']): ?>
                                        <div class="current-receipt mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6>Current Receipt</h6>
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <img src="../uploads/receipts/<?php echo htmlspecialchars($current_application['slep']); ?>" 
                                                                 class="img-thumbnail" style="max-height: 150px;" 
                                                                 alt="Payment Receipt">
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <a href="../uploads/receipts/<?php echo htmlspecialchars($current_application['slep']); ?>" 
                                                               class="btn btn-sm btn-info" target="_blank">
                                                                <i class="bi bi-eye"></i> View Full
                                                            </a>
                                                            <form action="delete_receipt.php" method="POST" class="d-inline">
                                                                <input type="hidden" name="application_id" value="<?php echo $current_application['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('Are you sure you want to delete this receipt?')">
                                                                    <i class="bi bi-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Roommates Section -->
                                        <div class="mt-4">
                                            <h5>Your Roommates</h5>
                                            <?php
                                            $roommates_query = "SELECT s.*, a.status 
                                                              FROM applications a
                                                              JOIN info s ON a.regnumber = s.regnumber
                                                              WHERE a.room_id = ? AND a.regnumber != ?";
                                            $roommates_stmt = $connection->prepare($roommates_query);
                                            $roommates_stmt->bind_param("is", $current_application['room_id'], $student_regnumber);
                                            $roommates_stmt->execute();
                                            $roommates = $roommates_stmt->get_result();
                                            
                                            if ($roommates->num_rows > 0):
                                                while ($roommate = $roommates->fetch_assoc()):
                                            ?>
                                                <div class="roommate-card">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($roommate['names']); ?></h6>
                                                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($roommate['regnumber']); ?></p>
                                                            <p class="mb-0 text-muted"><span class="text-dark fw-bold">College:</span> <?php echo htmlspecialchars($roommate['college']); ?></p>
                                                            <p class="mb-0 text-muted"><span class="text-dark fw-bold">School:</span> <?php echo htmlspecialchars($roommate['school']); ?></p>
                                                            <p class="mb-0 text-muted"><span class="text-dark fw-bold">Year:</span> <?php echo htmlspecialchars($roommate['yearofstudy']); ?></p>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <span class="badge bg-success">Roommate</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                                <p class="text-muted">No roommates assigned yet.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="../Dashboard/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hostelPins = document.querySelectorAll('.hostel-pin:not(.disabled)');
            const roomsContainer = document.querySelector('.rooms-container');
            const roomList = document.querySelector('.room-list');
            const prevPageBtn = document.querySelector('.prev-page');
            const nextPageBtn = document.querySelector('.next-page');
            const currentPageSpan = document.querySelector('.current-page');
            const totalPagesSpan = document.querySelector('.total-pages');
            
            let currentHostelId = null;
            let currentPage = 1;
            const roomsPerPage = 5;

            hostelPins.forEach(pin => {
                pin.addEventListener('click', function() {
                    // Remove active class from all pins
                    hostelPins.forEach(p => p.classList.remove('active'));
                    // Add active class to clicked pin
                    this.classList.add('active');
                    
                    currentHostelId = this.dataset.hostelId;
                    currentPage = 1;
                    loadRooms(currentHostelId, currentPage);
                });
            });

            function loadRooms(hostelId, page) {
                // Show rooms container
                roomsContainer.style.display = 'block';
                
                // Show loading state
                roomList.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                
                fetch(`get_rooms.php?hostel_id=${hostelId}&page=${page}&per_page=${roomsPerPage}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        roomList.innerHTML = '';
                        if (data.rooms.length === 0) {
                            roomList.innerHTML = '<div class="alert alert-info">No rooms available in this hostel.</div>';
                            return;
                        }
                        
                        data.rooms.forEach(room => {
                            const roomElement = document.createElement('div');
                            roomElement.className = 'room-item';
                            roomElement.innerHTML = `
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <h5 class="mb-0">Room ${room.room_code}</h5>
                                        <small class="text-muted">Capacity: ${room.capacity}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="room-status ${room.status === 'Available' ? 'status-available' : 'status-limited'}">
                                            ${room.remain} beds available
                                        </span>
                                        ${room.current_applications > 0 ? 
                                            `<small class="text-muted d-block">${room.current_applications} pending applications</small>` : 
                                            ''}
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <form action="apply_room.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to apply for this room?');">
                                            <input type="hidden" name="room_id" value="${room.id}">
                                            <input type="hidden" name="hostel_id" value="${hostelId}">
                                            <button type="submit" class="btn btn-primary btn-sm" 
                                                    ${room.remain <= 0 ? 'disabled' : ''}>
                                                ${room.remain <= 0 ? 'No Beds Available' : 'Apply Now'}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            `;
                            roomList.appendChild(roomElement);
                        });

                        // Update pagination
                        currentPageSpan.textContent = page;
                        totalPagesSpan.textContent = Math.ceil(data.total / roomsPerPage);
                        prevPageBtn.disabled = page === 1;
                        nextPageBtn.disabled = page >= Math.ceil(data.total / roomsPerPage);
                    })
                    .catch(error => {
                        roomList.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                        console.error('Error:', error);
                    });
            }

            prevPageBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadRooms(currentHostelId, currentPage);
                }
            });

            nextPageBtn.addEventListener('click', () => {
                currentPage++;
                loadRooms(currentHostelId, currentPage);
            });
        });
    </script>
</body>
</html> 