<?php
// session_start();
include 'connection.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: logout.php");
    exit();
}

// Set timezone to Kigali
date_default_timezone_set('Africa/Kigali');

$student_regnumber = $_SESSION['student_regnumber'];

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/claims/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_claim'])) {
    $room_id = mysqli_real_escape_string($connection, $_POST['room_id']);
    $message = mysqli_real_escape_string($connection, $_POST['message']);
    $category = mysqli_real_escape_string($connection, $_POST['category']);
    $image = '';
    
    // Validate image upload
    if (isset($_FILES['claim_image']) && $_FILES['claim_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['claim_image'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Check file type
        if (!in_array($file_extension, $allowed_extensions)) {
            echo "<script>alert('Error: Only JPG, JPEG, PNG & GIF files are allowed.'); window.history.back();</script>";
            exit();
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo "<script>alert('Error: File size must be less than 5MB.'); window.history.back();</script>";
            exit();
        }
        
        // Generate unique filename
        $new_filename = uniqid('claim_', true) . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $image = 'uploads/claims/' . $new_filename;
        } else {
            echo "<script>alert('Error uploading file. Please try again.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Please select an image to upload.'); window.history.back();</script>";
        exit();
    }
    
    // Get current timestamp in Kigali timezone
    $current_time = date('Y-m-d H:i:s');
    
    // Insert claim with image
    $insert_query = "INSERT INTO claiming (regnumber, room_id, message, category, status, created_at, updated_at, image) 
                     VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)";
    $stmt = $connection->prepare($insert_query);
    $stmt->bind_param("sisssss", $student_regnumber, $room_id, $message, $category, $current_time, $current_time, $image);
    
    if ($stmt->execute()) {
        echo "<script>alert('Claim submitted successfully!'); window.location.href='view_my_claims.php';</script>";
    } else {
        // Delete the uploaded file if database insert fails
        if (!empty($image) && file_exists('../' . $image)) {
            unlink('../' . $image);
        }
        echo "<script>alert('Error submitting claim: " . mysqli_error($connection) . "');</script>";
    }
}

// Get rooms that the student has applied for
$applied_rooms_query = "SELECT 
                          a.room_id,
                          r.room_code,
                          h.name as hostel_name,
                          h.building_code,
                          a.status as application_status,
                          a.created_at as applied_date,
                          COUNT(c.id) as claim_count
                       FROM applications a
                       JOIN rooms r ON a.room_id = r.id
                       JOIN hostels h ON r.hostel_id = h.id
                       LEFT JOIN claiming c ON a.regnumber = c.regnumber AND a.room_id = c.room_id
                       WHERE a.regnumber = ?
                       AND a.status != 'rejected'
                       GROUP BY a.room_id, r.room_code, h.name, h.building_code, a.status, a.created_at
                       ORDER BY a.created_at DESC";

$stmt = $connection->prepare($applied_rooms_query);
$stmt->bind_param("s", $student_regnumber);
$stmt->execute();
$applied_rooms = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Room Claim - UR-HOSTELS</title>
    <link href="../icon1.png" rel="icon">
    <link href="../icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
           body{
            background-color:#f6f9ff;
        }
        .room-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 4px 16px rgba(30, 60, 114, 0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
            overflow: hidden;
            position: relative;
        }

        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3c72, #2a5298, #667eea);
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.12);
        }

        .room-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .room-body {
            padding: 1.5rem;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .btn-action {
            padding: 0.4rem 1rem;
            border-radius: 5px;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 4px 16px rgba(30, 60, 114, 0.08);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3c72, #2a5298, #667eea);
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .info-card h5 {
            color: #1e3c72;
            margin-bottom: 1rem;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
            color: #444;
        }

        .info-list li i {
            color: #1e3c72;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }

        .modal-header {
            background: #f8f9fa;
            border-bottom: 1px solid #f0f0f0;
        }

        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #f0f0f0;
        }
    </style>
</head>

<body>
<div class="container mt-4">
   
    <main id="main" class="main">
        

        <section class="section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        
                        
                        <?php if ($applied_rooms->num_rows > 0): ?>
                            <p class="text-muted mb-4">Select a room you have applied for to submit a claim:</p>
                            
                            <?php while ($room = $applied_rooms->fetch_assoc()): ?>
                                <div class="room-card">
                                    <div class="room-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-2"><?php echo htmlspecialchars($room['hostel_name']); ?></h5>
                                                <p class="mb-2 text-muted">Building Code: <?php echo htmlspecialchars($room['building_code']); ?></p>
                                                <p class="mb-2"><strong>Room:</strong> <?php echo htmlspecialchars($room['room_code']); ?></p>
                                            </div>
                                            <span class="status-badge status-<?php echo $room['application_status']; ?>">
                                                <i class="bi bi-<?php echo $room['application_status'] == 'approved' ? 'check-circle' : 'clock'; ?>"></i>
                                                <?php echo ucfirst($room['application_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="room-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mb-2">
                                                    <i class="bi bi-calendar3 text-muted"></i>
                                                    <small class="text-muted ms-2">Applied on: <?php echo date('F j, Y', strtotime($room['applied_date'])); ?></small>
                                                </p>
                                                <?php if ($room['claim_count'] > 0): ?>
                                                    <p class="mb-0">
                                                        <i class="bi bi-chat-text text-muted"></i>
                                                        <small class="text-muted ms-2">Previous claims: <?php echo $room['claim_count']; ?></small>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-action" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#claimModal<?php echo $room['room_id']; ?>">
                                                <i class="bi bi-chat-text"></i> Submit Claim
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Claim Modal -->
                                <div class="modal fade" id="claimModal<?php echo $room['room_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Submit Claim for <?php echo htmlspecialchars($room['room_code']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Room Details</label>
                                                        <input type="text" class="form-control" 
                                                               value="<?php echo htmlspecialchars($room['hostel_name'] . ' - ' . $room['room_code']); ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="category" class="form-label">Claim Category *</label>
                                                        <select class="form-select" name="category" id="category" required>
                                                            <option value="">Select category</option>
                                                            <option value="Water">Water</option>
                                                            <option value="Toilets">Toilets</option>
                                                            <option value="Room Accessories">Room Accessories</option>
                                                            <option value="Electricity">Electricity</option>
                                                            <option value="Cleanliness">Cleanliness</option>
                                                            <option value="Other">Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="message" class="form-label">Claim Message *</label>
                                                        <textarea class="form-control" name="message" rows="5" 
                                                                  placeholder="Please describe your claim or reason for requesting this room..." required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="claim_image" class="form-label">Upload Image *</label>
                                                        <input type="file" class="form-control" id="claim_image" name="claim_image" accept="image/*" required>
                                                        <div class="form-text">Please upload an image related to your claim (JPG, PNG, GIF, max 5MB)</div>
                                                    </div>
                                                    
                                                    <?php if ($room['claim_count'] > 0): ?>
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            You have already submitted <?php echo $room['claim_count']; ?> claim(s) for this room. 
                                                            You can submit additional claims if needed.
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="submit_claim" class="btn btn-primary btn-action">
                                                        <i class="bi bi-send"></i> Submit Claim
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-house-exclamation"></i>
                                <h5>No Rooms Available for Claims</h5>
                                <p class="text-muted">You can only submit claims for rooms you have already applied for. Please apply for a room first, then you can submit a claim if needed.</p>
                                <a href="index.php" class="btn btn-primary btn-action">
                                    <i class="bi bi-house"></i> Go to Home
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <div class="info-card">
                            <h5><i class="bi bi-info-circle me-2"></i>Claim Information</h5>
                            <ul class="info-list">
                                <li><i class="bi bi-check-circle"></i> You can only claim rooms you have already applied for</li>
                                <li><i class="bi bi-chat-dots"></i> You can submit multiple claims for the same room</li>
                                <li><i class="bi bi-pencil-square"></i> Write a detailed message explaining your claim</li>
                                <li><i class="bi bi-send"></i> Submit your claim for review</li>
                                <li><i class="bi bi-clock-history"></i> Administrators will review and respond</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html> 