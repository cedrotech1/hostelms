<?php

include('connection.php');
include ('./includes/auth.php');
// checkUserRole(['information_modifier']);
// checkUserRole(['warefare']);


$userID = $_SESSION['id'];

$query = "SELECT campus FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$usercampus = '';
if ($row = $result->fetch_assoc()) {
    $usercampus = $row['campus'];
}


// Initialize variables

// Fetch campus name from DB
$query = "SELECT name FROM campuses WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $usercampus);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the campus name, fallback to ID if not found
$campusName = $usercampus;
if ($row = $result->fetch_assoc()) {
    $campusName = $row['name'];
}
$regnumber = $fullnames = $studentemail = $campus = $college = $school = $program = "";
$nid = $phone = "";
$message = $messageType = "";
$isViewing = false; // Flag to check if data is being viewed
$studentHasRoom = false;
$studentRoomInfo = null;

// Fetch student data by GET or POST
if ((isset($_POST['search']) && $_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['regnumber'])) || (isset($_GET['regnumber']) && !empty($_GET['regnumber']))) {
    $regnumber = isset($_POST['regnumber']) ? $_POST['regnumber'] : $_GET['regnumber'];
    $sql = "SELECT * FROM info WHERE regnumber = ? and campus=? ";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ss", $regnumber,$campusName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fullnames = $row['names'];
        $studentemail = $row['email'];
        $studentCampus = $row['campus'];
        $college = $row['college'];
        $school = $row['school'];
        $program = $row['program'];
        $nid = $row['nid'];
        $phoneNumber = $row['phone'];   
        $Gender = $row['gender'];       
        $yearofstudy = $row['yearofstudy'];   
        $intake = $row['intake'];   
        $disability = $row['disability'];   
        $isViewing = true;
        // Build student_data for eligibility and comparison
        $student_data = [
            'gender' => !empty($row['gender']) ? $row['gender'] : 'Not set',
            'yearofstudy' => !empty($row['yearofstudy']) ? $row['yearofstudy'] : 'Not set',
            'college' => !empty($row['college']) ? $row['college'] : 'Not set',
            'school' => !empty($row['school']) ? $row['school'] : 'Not set',
            'intake' => !empty($row['intake']) ? $row['intake'] : 'Not set',
            'disability' => isset($row['disability']) ? $row['disability'] : 'Not set'
        ];
        // Check for active application (not cancelled/rejected)    
        $appSql = "SELECT a.*, r.room_code, h.name AS hostel_name FROM applications a JOIN rooms r ON a.room_id = r.id JOIN hostels h ON r.hostel_id = h.id WHERE a.regnumber = ? AND a.status NOT IN ('cancelled', 'rejected') LIMIT 1";
        $appStmt = $connection->prepare($appSql);
        $appStmt->bind_param("s", $regnumber);
        $appStmt->execute();
        $appResult = $appStmt->get_result();
        if ($appResult->num_rows > 0) {
            $studentHasRoom = true;
            $studentRoomInfo = $appResult->fetch_assoc();
        }
        // Fetch hostels with at least one available bed (for assign form)
        $hostels = [];
        $hostelSql = "SELECT 
    h.id, 
    h.name, 
    h.gender, 
    h.year, 
    h.college, 
    h.school, 
    h.intake, 
    h.disability, 
    h.status, 
    SUM(r.remain) AS available_beds
FROM 
    hostels h
JOIN 
    rooms r ON r.hostel_id = h.id
WHERE 
    r.remain > 0 and h.campus_id='$usercampus'
GROUP BY 
    h.id, h.name, h.gender, h.year, h.college, h.school, h.intake, h.disability, h.status
HAVING 
    SUM(r.remain) > 0;
";
        $hostelResult = $connection->query($hostelSql);
        while ($hostelRow = $hostelResult->fetch_assoc()) {
            $hostels[] = $hostelRow;
        }
    } else {
        $message = "No student found with this registration number at your assigned campus";
        $messageType = "danger";
        $isViewing = false;
    }
}

// Update or insert student IDs (nid and phone)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $studentemail = $_POST['email'];
    $regnumber = $_POST['regnumber1'];
    $nid = $_POST['nid'];
    $phone = $_POST['phone'];
    $name = $_POST['names'];
    $sql = "UPDATE info SET names = ?, nid = ?, phone = ?,email=? WHERE regnumber = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("sssss", $name, $nid, $phone,$studentemail, $regnumber);
    if ($stmt->execute()) {
        header("Location: updateinfo.php?regnumber=" . urlencode($regnumber) . "&msg=updated");
        exit();
    } else {
        $message = "Error updating student data: " . $connection->error;
        $messageType = "danger";
    }
}

// Handle hostel application submission (assign new room)   
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_hostel'])) {
    $regnumber = $_POST['regnumber1'];
    $room_id = $_POST['room_id'];
    $status = 'pending';
    $slep = '';
    $now = date('Y-m-d H:i:s');
    // Check if student already has an active application
    $checkSql = "SELECT id FROM applications WHERE regnumber = ? AND status NOT IN ('cancelled', 'rejected')";
    $checkStmt = $connection->prepare($checkSql);
    $checkStmt->bind_param("s", $regnumber);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $message = "This student already has an active application.";
        $messageType = "danger";
    } else {
        // Check if room is available
        $roomCheckSql = "SELECT remain FROM rooms WHERE id = ?";
        $roomCheckStmt = $connection->prepare($roomCheckSql);
        $roomCheckStmt->bind_param("i", $room_id);
        $roomCheckStmt->execute();
        $roomResult = $roomCheckStmt->get_result();
        if ($roomRow = $roomResult->fetch_assoc()) {
            if ($roomRow['remain'] > 0) {
                $appSql = "INSERT INTO applications (regnumber, room_id, status, slep, created_at, updated_at, createdby) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $appStmt = $connection->prepare($appSql);
                $appStmt->bind_param("sisssss", $regnumber, $room_id, $status, $slep, $now, $now, $userID);
                if ($appStmt->execute()) {
                    $updateRoomSql = "UPDATE rooms SET remain = remain - 1 WHERE id = ? AND remain > 0";
                    $updateRoomStmt = $connection->prepare($updateRoomSql);
                    $updateRoomStmt->bind_param("i", $room_id);
                    $updateRoomStmt->execute();
                    header("Location: updateinfo.php?regnumber=" . urlencode($regnumber) . "&msg=assigned");
                    exit();
                } else {
                    $message = "Error assigning student to hostel: " . $connection->error;
                    $messageType = "danger";
                }
            } else {
                $message = "No available beds in the selected room.";
                $messageType = "danger";
            }
        } else {
            $message = "Selected room not found or not available.";
            $messageType = "danger";
        }
    }
}

// Handle hostel application submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reassign_room'])) {
    $regnumber = $_POST['regnumber1'];
    $new_room_id = $_POST['room_id'];
    $current_room_id = $_POST['current_room_id'];
    $now = date('Y-m-d H:i:s');
    // Find the active application
    $appSql = "SELECT id FROM applications WHERE regnumber = ? AND status NOT IN ('cancelled', 'rejected') LIMIT 1";
    $appStmt = $connection->prepare($appSql);
    $appStmt->bind_param("s", $regnumber);
    $appStmt->execute();
    $appResult = $appStmt->get_result();
    if ($appRow = $appResult->fetch_assoc()) {
        $application_id = $appRow['id'];
        // Update application to new room
        $updateAppSql = "UPDATE applications SET room_id = ?, updated_at = ? WHERE id = ?";
        $updateAppStmt = $connection->prepare($updateAppSql);
        $updateAppStmt->bind_param("isi", $new_room_id, $now, $application_id);
        if ($updateAppStmt->execute()) {
            // Increase remain in old room
            $incRoomSql = "UPDATE rooms SET remain = remain + 1 WHERE id = ?";
            $incRoomStmt = $connection->prepare($incRoomSql);
            $incRoomStmt->bind_param("i", $current_room_id);
            $incRoomStmt->execute();
            // Decrease remain in new room
            $decRoomSql = "UPDATE rooms SET remain = remain - 1 WHERE id = ? AND remain > 0";
            $decRoomStmt = $connection->prepare($decRoomSql);
            $decRoomStmt->bind_param("i", $new_room_id);
            $decRoomStmt->execute();
            header("Location: updateinfo.php?regnumber=" . urlencode($regnumber) . "&msg=reassigned");
            exit();
        } else {
            $message = "Error re-assigning room: " . $connection->error;
            $messageType = "danger";
        }
    } else {
        $message = "No active application found to re-assign.";
        $messageType = "danger";
    }
}

// In the HTML, add this after the PHP opening tag:
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $message = "Student data updated successfully.";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'assigned') {
        $message = "Student assigned to hostel successfully.";
        $messageType = "success";
    } elseif ($_GET['msg'] === 'reassigned') {
        $message = "Student room re-assigned successfully.";
        $messageType = "success";
    }
}

// Add eligibility function with reasons
function getHostelEligibilityReasons($hostel, $student) {
    $reasons = [];
    if (!in_array($hostel['status'], ['published', 'reserved'])) $reasons[] = 'Hostel is not open for applications.';
    if (!empty($hostel['gender']) && $student['gender'] !== 'Not set' && $hostel['gender'] !== $student['gender']) $reasons[] = 'Gender mismatch: Hostel is for ' . ucfirst($hostel['gender']) . ' students.';
    if (!empty($hostel['year']) && $student['yearofstudy'] !== 'Not set') {
        $years = array_map('trim', explode(',', $hostel['year']));
        if (!in_array($student['yearofstudy'], $years)) $reasons[] = 'Year mismatch: Hostel is for year(s) ' . implode(', ', $years) . '.';
    }
    if (!empty($hostel['college']) && $student['college'] !== 'Not set') {
        $colleges = array_map('trim', explode(',', $hostel['college']));
        if (!in_array($student['college'], $colleges)) $reasons[] = 'College mismatch: Hostel is for ' . implode(', ', $colleges) . '.';
    }
    if (!empty($hostel['school']) && $student['school'] !== 'Not set') {
        $schools = array_map('trim', explode(',', $hostel['school']));
        if (!in_array($student['school'], $schools)) $reasons[] = 'School mismatch: Hostel is for ' . implode(', ', $schools) . '.';
    }
    if (!empty($hostel['intake']) && $student['intake'] !== 'Not set') {
        $intakes = array_map('trim', explode(',', $hostel['intake']));
        if (!in_array($student['intake'], $intakes)) $reasons[] = 'Intake mismatch: Hostel is for intake(s) ' . implode(', ', $intakes) . '.';
    }
    if (isset($hostel['disability']) && $hostel['disability'] !== '' && $student['disability'] !== 'Not set') {
        if ((int)$hostel['disability'] !== (int)$student['disability']) $reasons[] = 'Disability mismatch: Hostel is for ' . ((int)$hostel['disability'] === 1 ? 'students with disabilities' : 'students without disabilities') . '.';
    } else if ($student['disability'] !== 'Not set') {
        if ((int)$student['disability'] === 1) $reasons[] = 'Disability mismatch: Hostel is not designed for students with disabilities.';
    }
    return $reasons;
}

// Add strict eligibility function
function isStudentEligibleForHostelStrict($hostel, $student) {
    if (!in_array($hostel['status'], ['published', 'reserved'])) return false;
    if (!empty($hostel['gender']) && $student['gender'] !== 'Not set' && $hostel['gender'] !== $student['gender']) return false;
    if (!empty($hostel['year']) && $student['yearofstudy'] !== 'Not set') {
        $years = array_map('trim', explode(',', $hostel['year']));
        if (!in_array($student['yearofstudy'], $years)) return false;
    }
    if (!empty($hostel['college']) && $student['college'] !== 'Not set') {
        $colleges = array_map('trim', explode(',', $hostel['college']));
        if (!in_array($student['college'], $colleges)) return false;
    }
    if (!empty($hostel['school']) && $student['school'] !== 'Not set') {
        $schools = array_map('trim', explode(',', $hostel['school']));
        if (!in_array($student['school'], $schools)) return false;
    }
    if (!empty($hostel['intake']) && $student['intake'] !== 'Not set') {
        $intakes = array_map('trim', explode(',', $hostel['intake']));
        if (!in_array($student['intake'], $intakes)) return false;
    }
    if (isset($hostel['disability']) && $hostel['disability'] !== '' && $student['disability'] !== 'Not set') {
        if ((int)$hostel['disability'] !== (int)$student['disability']) return false;
    } else if ($student['disability'] !== 'Not set') {
        if ((int)$student['disability'] === 1) return false;
    }
    return true;
}

function getHostelEligibilityMatchReasons($hostel, $student) {
    $matches = [];
    if (in_array($hostel['status'], ['published', 'reserved'])) $matches[] = 'Hostel is open for applications.';
    if (!empty($hostel['gender']) && $student['gender'] !== 'Not set' && $hostel['gender'] === $student['gender']) $matches[] = 'Gender matches.';
    if (!empty($hostel['year']) && $student['yearofstudy'] !== 'Not set') {
        $years = array_map('trim', explode(',', $hostel['year']));
        if (in_array($student['yearofstudy'], $years)) $matches[] = 'Year matches.';
    }
    if (!empty($hostel['college']) && $student['college'] !== 'Not set') {
        $colleges = array_map('trim', explode(',', $hostel['college']));
        if (in_array($student['college'], $colleges)) $matches[] = 'College matches.';
    }
    if (!empty($hostel['school']) && $student['school'] !== 'Not set') {
        $schools = array_map('trim', explode(',', $hostel['school']));
        if (in_array($student['school'], $schools)) $matches[] = 'School matches.';
    }
    if (!empty($hostel['intake']) && $student['intake'] !== 'Not set') {
        $intakes = array_map('trim', explode(',', $hostel['intake']));
        if (in_array($student['intake'], $intakes)) $matches[] = 'Intake matches.';
    }
    if (isset($hostel['disability']) && $hostel['disability'] !== '' && $student['disability'] !== 'Not set') {
        if ((int)$hostel['disability'] === (int)$student['disability']) $matches[] = 'Disability matches.';
    } else if ($student['disability'] !== 'Not set') {
        if ((int)$student['disability'] === 0) $matches[] = 'No disability required.';
    }
    return $matches;
}

// Collect debug info for JS console
$debug_js = [];
$debug_js[] = 'Session campus: ' . (isset($_SESSION['campus']) ? $_SESSION['campus'] : 'NOT SET');
$debug_js[] = 'usercampus: ' . (isset($usercampus) ? $usercampus : 'NOT SET');
$debug_js[] = 'campusName: ' . (isset($campusName) ? $campusName : 'NOT SET');
if (isset($studentCampus)) $debug_js[] = 'studentCampus: ' . $studentCampus;
if (isset($regnumber)) $debug_js[] = 'regnumber: ' . $regnumber;
if (isset($sql)) $debug_js[] = 'Last SQL: ' . $sql;
if (!empty($message)) $debug_js[] = 'Message: ' . $message;
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UR-HOSTELS</title>
    <link href="./icon1.png" rel="icon">
    <link href="./icon1.png" rel="apple-touch-icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
<?php
  include ("./includes/header.php");
  include ("./includes/menu.php");
  ?>

    
    <main id="main" class="main">
        <section class="section dashboard">
            <div class="row">

                <!-- Display message card if there's a message -->
                <?php if ($message): ?>
                    <div class="col-lg-12">
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Search Card -->
                <div class="col-lg-6 mb-4">
                    <div class="card p-3">
                        <h5 class="card-title">Search Student from <?php echo htmlspecialchars($campusName); ?></h5>
                        <form method="POST" action="">
                            <div class="input-group">
                                <span class="input-group-text">REG NUMBER</span>
                                <input type="text" name="regnumber" class="form-control" required>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" name="search" class="btn btn-primary">Search</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Student Info Card -->
                <?php if ($isViewing): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card p-3">
                            <h5 class="card-title">Student Information</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Reg Number:</strong> <?php echo htmlspecialchars($regnumber); ?></li>
                                <li class="list-group-item"><strong>Names:</strong> <?php echo htmlspecialchars($fullnames); ?></li>
                                <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($studentemail); ?></li>
                                <li class="list-group-item"><strong>Phone:</strong> <?php echo htmlspecialchars($phoneNumber); ?></li>
                                <li class="list-group-item"><strong>National ID (NID):</strong> <?php echo htmlspecialchars($nid); ?></li>
                                <li class="list-group-item"><strong>Campus:</strong> <?php echo htmlspecialchars($studentCampus); ?></li>
                                <li class="list-group-item"><strong>College:</strong> <?php echo htmlspecialchars($college); ?></li>
                                <li class="list-group-item"><strong>School:</strong> <?php echo htmlspecialchars($school); ?></li>
                                <li class="list-group-item"><strong>Program:</strong> <?php echo htmlspecialchars($program); ?></li>
                                <li class="list-group-item"><strong>Gender:</strong> <?php echo htmlspecialchars($Gender); ?></li>
                                 <li class="list-group-item"><strong>Year of study:</strong> <?php echo htmlspecialchars($yearofstudy); ?></li>
                           
                            </ul>
                        </div>
                    </div>
                    <?php if ($studentHasRoom && $studentRoomInfo): ?>
                        <!-- Room Info Card -->
                        <div class="col-lg-6 mb-4">
                            <div class="card p-3 border-success">
                                <h5 class="card-title text-success">Assigned Room</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Hostel:</strong> <?php echo htmlspecialchars($studentRoomInfo['hostel_name']); ?></li>
                                    <li class="list-group-item"><strong>Room:</strong> <?php echo htmlspecialchars($studentRoomInfo['room_code']); ?></li>
                                    <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($studentRoomInfo['status'])); ?></li>
                                    <li class="list-group-item"><strong>Assigned At:</strong> <?php echo htmlspecialchars($studentRoomInfo['created_at']); ?></li>
                                </ul>
                                <button type="button" class="btn btn-warning mt-3" data-bs-toggle="modal" data-bs-target="#reassignRoomModal">Re-assign Room</button>
                            </div>
                        </div>

                        <!-- Re-assign Room Modal -->
                        <div class="modal fade" id="reassignRoomModal" tabindex="-1" aria-labelledby="reassignRoomModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="reassignRoomModalLabel">Re-assign Room</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <form method="POST" action="" id="reassignRoomForm">
                                    <input type="hidden" name="regnumber1" value="<?php echo htmlspecialchars($regnumber); ?>">
                                    <input type="hidden" name="current_room_id" value="<?php echo htmlspecialchars($studentRoomInfo['room_id']); ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Select Hostel</label>
                                        <div class="d-flex flex-wrap gap-3" id="hostelCardsModal">
                                            <?php
                                            $anyEligible = false;
                                            $ineligibleHostels = [];
                                            foreach ($hostels as $hostel) {
                                                if (isStudentEligibleForHostelStrict($hostel, $student_data)) {
                                                    $anyEligible = true;
                                                    $matches = getHostelEligibilityMatchReasons($hostel, $student_data);
                                            ?>
                                                <div class="card hostel-card-modal shadow-sm" style="min-width: 260px; max-width: 300px; cursor:pointer; transition: box-shadow 0.2s;background-color:whitesmoke;border:2px solid gray" data-hostel-id="<?php echo $hostel['id']; ?>">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title mb-2 text-primary fw-bold"><?php echo htmlspecialchars($hostel['name']); ?></h5>
                                                        <div class="mb-2">
                                                            <span class="badge bg-info"><i class="bi bi-person-bounding-box me-1"></i>Beds: <?php echo $hostel['available_beds']; ?></span>
                                                        </div>
                                                        <div class="mb-2 text-muted small" id="hostel-campus-modal-<?php echo $hostel['id']; ?>">
                                                            <i class="bi bi-geo-alt me-1"></i>Loading campus...
                                                        </div>
                                                        <div class="mb-2 text-muted small" id="hostel-meta-modal-<?php echo $hostel['id']; ?>"></div>
                                                    </div>
                                                </div>
                                            <?php } else {
                                                $ineligibleHostels[] = $hostel; 
                                            }
                                            }
                                            if (!$anyEligible) { ?>
                                                <div class="alert alert-warning w-100 mt-3">No hostels available for this student based on their profile.</div>
                                            <?php }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="roomsSectionModal" style="display:none;">
                                        <label class="form-label">Select Room</label>
                                        <div class="d-flex flex-wrap gap-3" id="roomCardsModal">
                                            <!-- Room cards will be loaded here -->
                                        </div>
                                    </div>
                                    <button type="submit" name="reassign_room" class="btn btn-success mt-3" id="assignBtnModal" style="display:none;">Re-assign to Hostel</button>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                    <?php else: ?>
                        <div class="col-lg-12 mb-4">
                            <div class="alert alert-info">Student is not assigned to any room.</div>
                            <button type="button" class="btn btn-primary" id="showAssignFormBtn">Assign New Hostel</button>
                        </div>
                        <!-- Assign Room Card -->
                        <div class="col-lg-12 mb-4" id="assignRoomCard" style="display:none;">
                            <div class="card p-3 border-primary">
                                <h5 class="card-title text-primary">Assign Room</h5>
                                <form method="POST" action="" id="assignRoomForm">
                                    <input type="hidden" name="regnumber1" value="<?php echo htmlspecialchars($regnumber); ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Select Hostel</label>
                                        <div class="d-flex flex-wrap gap-3" id="hostelCards">
                                            <?php
                                            $anyEligible = false;
                                            $ineligibleHostels = [];
                                            foreach ($hostels as $hostel) {
                                                if (isStudentEligibleForHostelStrict($hostel, $student_data)) {
                                                    $anyEligible = true;
                                                    $matches = getHostelEligibilityMatchReasons($hostel, $student_data);
                                            ?>
                                                <div class="card hostel-card shadow-sm" style="min-width: 260px; max-width: 300px; cursor:pointer; transition: box-shadow 0.2s;background-color:whitesmoke;border:2px solid gray" data-hostel-id="<?php echo $hostel['id']; ?>">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title mb-2 text-primary fw-bold"><?php echo htmlspecialchars($hostel['name']); ?></h5>
                                                        <div class="mb-2">
                                                            <span class="badge bg-info"><i class="bi bi-person-bounding-box me-1"></i>Beds: <?php echo $hostel['available_beds']; ?></span>
                                                        </div>
                                                        <div class="mb-2 text-muted small" id="hostel-campus-<?php echo $hostel['id']; ?>">
                                                            <i class="bi bi-geo-alt me-1"></i>Loading campus...
                                                        </div>
                                                        <div class="mb-2 text-muted small" id="hostel-meta-<?php echo $hostel['id']; ?>"></div>
                                                    </div>
                                                </div>
                                            <?php } else {
                                                $ineligibleHostels[] = $hostel; 
                                            }
                                            }
                                            if (!$anyEligible) { ?>
                                                <div class="alert alert-warning w-100 mt-3">No hostels available for this student based on their profile.</div>
                                            <?php }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="roomsSection" style="display:none;">
                                        <label class="form-label">Select Room</label>
                                        <div class="d-flex flex-wrap gap-3" id="roomCards">
                                            <!-- Room cards will be loaded here -->
                                        </div>
                                    </div>
                                    <button type="submit" name="assign_hostel" class="btn btn-success mt-3" id="assignBtn" style="display:none;">Assign to Hostel</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- Update Info Card -->
                    <div class="col-lg-6 mb-4">
                        <div class="card p-3 border-warning">
                            <h5 class="card-title text-warning">Update Student Info</h5>
                            <form method="POST" action="">
                                <input type="hidden" name="regnumber1" value="<?php echo htmlspecialchars($regnumber); ?>">
                                <div class="mb-3">
                                    <label for="names" class="form-label">Names</label>
                                    <input type="text" name="names" class="form-control" value="<?php echo htmlspecialchars($fullnames); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($studentemail); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phoneNumber); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="nid" class="form-label">National ID (NID)</label>
                                    <input type="text" name="nid" class="form-control" value="<?php echo htmlspecialchars($nid); ?>" required>
                                </div>
                                <button type="submit" name="update" class="btn btn-warning">Update Info</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include("./includes/footer.php"); ?>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch and display campus and meta info for each hostel card
        document.querySelectorAll('.hostel-card').forEach(function(card) {
            var hostelId = card.getAttribute('data-hostel-id');
            fetch('get_hostel_details.php?id=' + hostelId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('hostel-campus-' + hostelId).innerHTML =
                            `<i class='bi bi-geo-alt me-1'></i>${data.hostel.campus_name}`;
                        let meta = '';
                        if (data.hostel.gender) meta += `<span class='me-2'><i class='bi bi-gender-${data.hostel.gender === 'male' ? 'male' : 'female'}'></i> ${data.hostel.gender.charAt(0).toUpperCase() + data.hostel.gender.slice(1)}</span>`;
                        if (data.hostel.year) meta += `<span class='me-2'><i class='bi bi-calendar'></i> Year: ${data.hostel.year}</span>`;
                        if (data.hostel.building_code) meta += `<span class='me-2'><i class='bi bi-building'></i> ${data.hostel.building_code}</span>`;
                        document.getElementById('hostel-meta-' + hostelId).innerHTML = meta;
                    } else {
                        document.getElementById('hostel-campus-' + hostelId).innerHTML = '<span class="text-danger">Campus info unavailable</span>';
                    }
                });
            // Add hover effect
            card.addEventListener('mouseenter', function() {
                card.style.boxShadow = '0 0 0.5remrgb(61, 253, 13), 0 0.5rem 1rem rgba(0,0,0,0.05)';
            });
            card.addEventListener('mouseleave', function() {
                card.style.boxShadow = '';
            });
        });
        // Hostel card click handler
        document.querySelectorAll('.hostel-card').forEach(function(card) {
            card.addEventListener('click', function() {
                var hostelId = this.getAttribute('data-hostel-id');
                // Highlight selected hostel
                document.querySelectorAll('.hostel-card').forEach(function(c) { c.classList.remove('border-primary', 'border-3'); });
                this.classList.add('border-primary', 'border-3');
                // Fetch rooms for this hostel
                var roomSection = document.getElementById('roomsSection');
                var roomCards = document.getElementById('roomCards');
                roomSection.style.display = 'block';
                roomCards.innerHTML = '<div>Loading rooms...</div>';
                fetch('get_available_rooms.php?hostel_id=' + hostelId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            roomCards.innerHTML = '<div class="alert alert-warning">No available rooms in this hostel.</div>';
                            document.getElementById('assignBtn').style.display = 'none';
                            return;
                        }
                        let html = '';
                        if (data.length > 0) {
                            html += `<div class=\"table-responsive\"><table class=\"table table-sm  align-middle mb-0\">`;
                            html += `<thead class=\"table-light\"><tr><th></th><th>Room Code</th><th>Status</th><th>Beds</th><th>Available</th></tr></thead><tbody>`;
                            data.forEach(function(room) {
                                html += `<tr>` +
                                        `<td><input type=\"radio\" name=\"room_id\" value=\"${room.id}\" class=\"form-check-input\"></td>` +
                                        `<td class=\"fw-bold small\">${room.room_code}</td>` +
                                        `<td><span class=\"badge ${room.status === 'reserved' ? 'bg-success' : 'bg-secondary'}\">${room.status.charAt(0).toUpperCase() + room.status.slice(1)}</span></td>` +
                                        `<td>${room.number_of_beds !== undefined ? room.number_of_beds : '-'}</td>` +
                                        `<td>${room.remain !== undefined ? room.remain : '-'}</td>` +
                                        `</tr>`;
                            });
                            html += `</tbody></table></div>`;
                        }
                        roomCards.innerHTML = html;
                        document.getElementById('assignBtn').style.display = 'block';
                        // Room card click handler
                        document.querySelectorAll('.room-card').forEach(function(card) {
                            card.addEventListener('click', function(e) {
                                // Only select radio if not clicking the radio directly
                                if (e.target.tagName !== 'INPUT') {
                                    this.querySelector('input[type=radio]').checked = true;
                                }
                                document.querySelectorAll('.room-card').forEach(function(c) { c.classList.remove('border-success', 'border-3'); });
                                this.classList.add('border-success', 'border-3');
                            });
                        });
                    });
            });
        });
        // Prevent form submit if no room selected
        var assignForm = document.getElementById('assignRoomForm');
        if (assignForm) {
            assignForm.addEventListener('submit', function(e) {
                var checked = document.querySelector('input[name="room_id"]:checked');
                if (!checked) {
                    e.preventDefault();
                    alert('Please select a room.');
                }
            });
        }
        // Modal logic for re-assign
        document.querySelectorAll('.hostel-card-modal').forEach(function(card) {
            var hostelId = card.getAttribute('data-hostel-id');
            fetch('get_hostel_details.php?id=' + hostelId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('hostel-campus-modal-' + hostelId).innerHTML =
                            `<i class='bi bi-geo-alt me-1'></i>${data.hostel.campus_name}`;
                        let meta = '';
                        if (data.hostel.gender) meta += `<span class='me-2'><i class='bi bi-gender-${data.hostel.gender === 'male' ? 'male' : 'female'}'></i> ${data.hostel.gender.charAt(0).toUpperCase() + data.hostel.gender.slice(1)}</span>`;
                        if (data.hostel.year) meta += `<span class='me-2'><i class='bi bi-calendar'></i> Year: ${data.hostel.year}</span>`;
                        if (data.hostel.building_code) meta += `<span class='me-2'><i class='bi bi-building'></i> ${data.hostel.building_code}</span>`;
                        document.getElementById('hostel-meta-modal-' + hostelId).innerHTML = meta;
                    } else {
                        document.getElementById('hostel-campus-modal-' + hostelId).innerHTML = '<span class="text-danger">Campus info unavailable</span>';
                    }
                });
            card.addEventListener('mouseenter', function() {
                card.style.boxShadow = '0 0 0.5rem #0d6efd, 0 0.5rem 1rem rgba(0,0,0,0.05)';
            });
            card.addEventListener('mouseleave', function() {
                card.style.boxShadow = '';
            });
            card.addEventListener('click', function() {
                document.querySelectorAll('.hostel-card-modal').forEach(function(c) { c.classList.remove('border-primary', 'border-3'); });
                this.classList.add('border-primary', 'border-3');
                var roomSection = document.getElementById('roomsSectionModal');
                var roomCards = document.getElementById('roomCardsModal');
                roomSection.style.display = 'block';
                roomCards.innerHTML = '<div>Loading rooms...</div>';
                fetch('get_available_rooms.php?hostel_id=' + hostelId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            roomCards.innerHTML = '<div class="alert alert-warning">No available rooms in this hostel.</div>';
                            document.getElementById('assignBtnModal').style.display = 'none';
                            return;
                        }
                        let html = '';
                        html += `<div class=\"table-responsive\"><table class=\"table table-sm  align-middle mb-0\">`;
                        html += `<thead class=\"table-light\"><tr><th></th><th>Room Code</th><th>Status</th><th>Beds</th><th>Available</th></tr></thead><tbody>`;
                        data.forEach(function(room) {
                            html += `<tr>` +
                                    `<td><input type=\"radio\" name=\"room_id\" value=\"${room.id}\" class=\"form-check-input\"></td>` +
                                    `<td class=\"fw-bold small\">${room.room_code}</td>` +
                                    `<td><span class=\"badge ${room.status === 'reserved' ? 'bg-success' : 'bg-secondary'}\">${room.status.charAt(0).toUpperCase() + room.status.slice(1)}</span></td>` +
                                    `<td>${room.number_of_beds !== undefined ? room.number_of_beds : '-'}</td>` +
                                    `<td>${room.remain !== undefined ? room.remain : '-'}</td>` +
                                    `</tr>`;
                        });
                        html += `</tbody></table></div>`;
                        roomCards.innerHTML = html;
                        document.getElementById('assignBtnModal').style.display = 'block';
                    });
            });
        });
        // Attach event listener only when modal is shown
        var reassignModal = document.getElementById('reassignRoomModal');
        if (reassignModal) {
            reassignModal.addEventListener('shown.bs.modal', function () {
                const reassignForm = document.getElementById('reassignRoomForm');
                if (reassignForm) {
                    reassignForm.onsubmit = function(e) {
                        var checked = document.querySelector('#roomCardsModal input[name="room_id"]:checked');
                        if (!checked) {
                            e.preventDefault();
                            alert('Please select a room.');
                        }
                    };
                }
            });
        }
        var showAssignFormBtn = document.getElementById('showAssignFormBtn');
        var assignRoomCard = document.getElementById('assignRoomCard');
        if (showAssignFormBtn && assignRoomCard) {
            showAssignFormBtn.addEventListener('click', function() {
                assignRoomCard.style.display = 'block';
                showAssignFormBtn.style.display = 'none';
            });
        }
    });
    </script>
    <script>
<?php foreach ($debug_js as $dbg): ?>
    console.log(<?php echo json_encode($dbg); ?>);
<?php endforeach; ?>
</script>
</body>

</html>
