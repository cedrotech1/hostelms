<?php
// session_start();
require_once 'room_list.php';

// Function to get hostel statistics
function getHostelStats($connection, $hostel_id) {
    $stats = [
        'total_rooms' => 0,
        'available_rooms' => 0,
        'total_beds' => 0,
        'available_beds' => 0
    ];
    
    $query = "SELECT 
                COUNT(*) as total_rooms,
                SUM(CASE WHEN remain > 0 THEN 1 ELSE 0 END) as available_rooms,
                SUM(number_of_beds) as total_beds,
                SUM(remain) as available_beds
              FROM rooms 
              WHERE hostel_id = ? AND status='published'";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $connection->error);
        return $stats;
    }
    $stmt->bind_param("i", $hostel_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return $stats;
    }
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $room_stats = $result->fetch_assoc();
        $stats['total_rooms'] = $room_stats['total_rooms'] ?? 0;
        $stats['available_rooms'] = $room_stats['available_rooms'] ?? 0;
        $stats['total_beds'] = $room_stats['total_beds'] ?? 0;
        $stats['available_beds'] = $room_stats['available_beds'] ?? 0;
    }
    
    $stmt->close();
    return $stats;
}

$studentid = $_SESSION['student_id'] ?? '';

// Function to check if student is eligible for hostel and return reason if ineligible
function isStudentEligibleForHostel($hostel, $student_data, &$reason = '') {
    // Check gender match
    if (!empty($hostel['gender']) && $hostel['gender'] !== $student_data['gender']) {
        $reason = "Your gender (" . ($student_data['gender'] === 'F' ? 'Female' : 'Male') . ") does not match the hostel requirement (" . ($hostel['gender'] === 'F' ? 'Female' : 'Male') . ").";
        return false;
    }

    // Check year match
    if (!empty($hostel['year'])) {
        $hostel_years = array_map('trim', explode(',', $hostel['year']));
        if (!in_array($student_data['yearofstudy'], $hostel_years)) {
            $reason = "Your year of study ({$student_data['yearofstudy']}) does not match the hostel requirement (" . implode(', ', $hostel_years) . ").";
            return false;
        }
    }

    // Check college match
    if (!empty($hostel['college']) && !empty($student_data['college'])) {
        $hostel_colleges = array_map('trim', explode(',', $hostel['college']));
        if (!in_array($student_data['college'], $hostel_colleges)) {
            $reason = "Your college ({$student_data['college']}) does not match the hostel requirement (" . implode(', ', $hostel_colleges) . ").";
            return false;
        }
    }

    // Check school match
    if (!empty($hostel['school']) && !empty($student_data['school'])) {
        $hostel_schools = array_map('trim', explode(',', $hostel['school']));
        if (!in_array($student_data['school'], $hostel_schools)) {
            $reason = "Your school ({$student_data['school']}) does not match the hostel requirement (" . implode(', ', $hostel_schools) . ").";
            return false;
        }
    }

    // Check intake match
    if (!empty($hostel['intake']) && !empty($student_data['intake'])) {
        $hostel_intakes = array_map('trim', explode(',', $hostel['intake']));
        if (!in_array($student_data['intake'], $hostel_intakes)) {
            $reason = "Your intake ({$student_data['intake']}) does not match the hostel requirement (" . implode(', ', $hostel_intakes) . ").";
            return false;
        }
    }

    // Check disability match
    if (!empty($hostel['disability'])) {
        $hostel_disability = intval($hostel['disability']);
        $student_disability = isset($student_data['disability']) ? intval($student_data['disability']) : 0;
        if ($hostel_disability !== $student_disability) {
            $reason = "Your disability status (" . getDisabilityText($student_disability) . ") does not match the hostel requirement (" . getDisabilityText($hostel_disability) . ").";
            return false;
        }
    } else {
        $student_disability = isset($student_data['disability']) ? intval($student_data['disability']) : 0;
        if ($student_disability === 1) {
            $reason = "This hostel does not support students with disabilities.";
            return false;
        }
    }

    return true;
}

// Helper function to get disability text
function getDisabilityText($disability_value) {
    return intval($disability_value) === 1 ? 'Students with disabilities' : 'Students without disabilities';
}

// Function to check if hostel has available rooms
function hasAvailableRooms($connection, $hostel_id) {
    $query = "SELECT COUNT(*) as available_rooms 
              FROM rooms 
              WHERE hostel_id = ? AND remain > 0 AND status='published'";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed for hasAvailableRooms: " . $connection->error);
        return false;
    }
    $stmt->bind_param("i", $hostel_id);
    if (!$stmt->execute()) {
        error_log("Execute failed for hasAvailableRooms: " . $stmt->error);
        return false;
    }
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $available = $row['available_rooms'] > 0;
        $stmt->close();
        return $available;
    }
    
    $stmt->close();
    return false;
}

// Main function to display hostel card with eligibility check
function displayHostelCard($hostel, $connection, &$reasons = []) {
    // Get hostel statistics
    $stats = getHostelStats($connection, $hostel['id']);
    
    // Get student data from session with safe defaults
    $student_data = [
        'gender' => $_SESSION['student_gender'] ?? '',
        'yearofstudy' => $_SESSION['student_year'] ?? '',
        'college' => $_SESSION['student_college'] ?? '',
        'school' => $_SESSION['student_school'] ?? '',
        'intake' => $_SESSION['student_intake'] ?? '',
        'disability' => $_SESSION['student_disability'] ?? 0
    ];
    
    // Check if required session data is missing
    if (empty($student_data['gender']) || empty($student_data['yearofstudy'])) {
        $reasons[] = "Your profile is incomplete (missing gender or year of study). Please update your profile.";
        return;
    }
    
    // Check eligibility
    $reason = '';
    $isEligible = isStudentEligibleForHostel($hostel, $student_data, $reason);
    $hasRooms = hasAvailableRooms($connection, $hostel['id']);
    $isPublished = ($hostel['status'] === 'published');
    
    // Collect reasons for not displaying the card
    if (!$isEligible) {
        $reasons[] = "You are not eligible for {$hostel['name']}: $reason";
        return;
    }
    if (!$isPublished) {
        $reasons[] = "{$hostel['name']} is not currently available (status: {$hostel['status']}).";
        return;
    }
    if (!$hasRooms) {
        $reasons[] = "{$hostel['name']} has no available rooms.";
        return;
    }
    
    // Log for debugging
    error_log("Displaying hostel: {$hostel['name']} (ID: {$hostel['id']}, Campus: {$hostel['campus_name']})");
    
    ?>
    <div class="hostel-card-item my-2">
        <div class="card h-100 shadow-sm">
            <div class="card-header   text-white d-flex justify-content-between align-items-center" style="background-color:rgb(28, 50, 73);">
                <span class="fw-bold"><i class="bi bi-building me-2"></i><?php echo htmlspecialchars($hostel['name']); ?></span>
                <span class="badge " style="background-color:rgb(240, 242, 245);color:rgb(28, 50, 73);"><i class="bi bi-door-open me-1"></i> <?php echo $stats['available_rooms']; ?> Rooms Available</span>
            </div>
            <div class="card-body">
                <div class="stats-container mb-4">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="stat-card pending-beds">
                                <div class="stat-icon">
                                    <i class="bi bi-building me-2"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $stats['available_rooms']; ?></div>
                                    <div class="stat-label">rooms</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card total-beds">
                                <div class="stat-icon">
                                    <i class="bi bi-upload"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $stats['total_beds']; ?></div>
                                    <div class="stat-label">Beds</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card available-beds">
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $stats['available_beds']; ?></div>
                                    <div class="stat-label">Available</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="fw-bold mb-1"><i class="bi bi-people me-1"></i> Allowed Students</div>
                    <div class="row g-2">
                        <?php
                        $hasRestriction = false;
                        
                        // Campus
                        if (!empty($hostel['campus_name'])) {
                            $hasRestriction = true;
                            echo '<div class="col-6">
                                    <div class="card h-100 border-info shadow-sm" style="border-radius: 12px;">
                                        <div class="card-header py-1 px-2 bg-info text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">
                                            Campus
                                        </div>
                                        <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                            <span class="allowed-pill">' . htmlspecialchars($hostel['campus_name']) . '</span>
                                        </div>
                                    </div>
                                </div>';
                        }
                        
                        // Gender
                        if (!empty($hostel['gender'])) {
                            $hasRestriction = true;
                            echo '<div class="col-6">
                                    <div class="card h-100 border-primary shadow-sm" style="border-radius: 12px;">
                                        <div class="card-header py-1 px-2 bg-primary text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">
                                            Gender
                                        </div>
                                        <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                            <span class="allowed-pill">' . htmlspecialchars($hostel['gender'] === 'F' ? 'Female' : 'Male') . '</span>
                                        </div>
                                    </div>
                                </div>';
                        }
                        
                        // Year
                        if (!empty($hostel['year'])) {
                            $hasRestriction = true;
                            $years = array_map('trim', explode(',', $hostel['year']));
                            echo '<div class="col-6"><div class="card h-100 border-success shadow-sm" style="border-radius: 12px;">
                                    <div class="card-header py-1 px-2 bg-success text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">Year of Study</div>
                                    <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">';
                            if (count($years) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($years as $year) {
                                    echo '<li class="allowed-pill">Year ' . htmlspecialchars($year) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span class="allowed-pill">Year ' . htmlspecialchars($years[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        
                        // School
                        if (!empty($hostel['school'])) {
                            $hasRestriction = true;
                            $schools = array_map('trim', explode(',', $hostel['school']));
                            echo '<div class="col-6"><div class="card h-100 border-info shadow-sm" style="border-radius: 12px;">
                                    <div class="card-header py-1 px-2 bg-info text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">School</div>
                                    <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">';
                            if (count($schools) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($schools as $school) {
                                    echo '<li class="allowed-pill">' . htmlspecialchars($school) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span class="allowed-pill">' . htmlspecialchars($schools[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        
                        // College
                        if (!empty($hostel['college'])) {
                            $hasRestriction = true;
                            $colleges = array_map('trim', explode(',', $hostel['college']));
                            echo '<div class="col-6"><div class="card h-100 border-warning shadow-sm" style="border-radius: 12px;">
                                    <div class="card-header py-1 px-2 bg-warning text-dark text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">College</div>
                                    <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">';
                            if (count($colleges) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($colleges as $college) {
                                    echo '<li class="allowed-pill">' . htmlspecialchars($college) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span class="allowed-pill">' . htmlspecialchars($colleges[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        
                        // Intake
                        if (!empty($hostel['intake'])) {
                            $hasRestriction = true;
                            $intakes = array_map('trim', explode(',', $hostel['intake']));
                            echo '<div class="col-6"><div class="card h-100 border-secondary shadow-sm" style="border-radius: 12px;">
                                    <div class="card-header py-1 px-2 bg-secondary text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">Intake</div>
                                    <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">';
                            if (count($intakes) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($intakes as $intake) {
                                    echo '<li class="allowed-pill">' . htmlspecialchars($intake) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span class="allowed-pill">' . htmlspecialchars($intakes[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        
                        // Disability
                        if (!empty($hostel['disability'])) {
                            $hasRestriction = true;
                            echo '<div class="col-6"><div class="card h-100 border-dark shadow-sm" style="border-radius: 12px;">
                                    <div class="card-header py-1 px-2 bg-dark text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">Disability</div>
                                    <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                        <span class="allowed-pill"><i class="bi bi-wheelchair me-1"></i>' . getDisabilityText($hostel['disability']) . '</span>
                                    </div></div></div>';
                        }
                        
                        // If no restrictions
                        if (!$hasRestriction) {
                            echo '<div class="col-12"><div class="card h-100 border-success shadow-sm" style="border-radius: 12px;">
                                    <div class="card-header py-1 px-2 bg-success text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">All Students</div>
                                    <div class="card-body py-2 px-2 text-center text-success" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                        <i class="bi bi-check-circle me-1"></i>All students are allowed for this hostel
                                    </div></div></div>';
                        }
                        ?>
                    </div>
                </div>
                
                <button style="background-color:rgb(28, 50, 73);color:white;" type="button" 
                        class="btn  w-100 view-rooms-btn"
                        data-bs-toggle="modal" 
                        data-bs-target="#roomsModal"
                        data-hostel-id="<?php echo $hostel['id']; ?>"
                        data-hostel-name="<?php echo htmlspecialchars($hostel['name']); ?>">
                    <i class="bi bi-door-open me-2"></i> View Available Rooms
                </button>
            </div>
        </div>
    </div>
    <?php
}

// Function to display a card explaining why no hostels are available
function displayNoHostelsCard($reasons = []) {
    ?>
    <div class="hostel-card-item">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-info-circle me-2"></i>No Hostels Available</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Why No Hostels Are Available</div>
                    <?php if (empty($reasons)) : ?>
                        <p class="text-muted">No specific reasons identified. Please contact support for assistance.</p>
                    <?php else : ?>
                        <ul class="list-unstyled allowed-list">
                            <?php foreach (array_slice($reasons, 0, 5) as $reason) : ?>
                                <li class="allowed-pill"><i class="bi bi-x-circle me-1"></i><?php echo htmlspecialchars($reason); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($reasons) > 5) : ?>
                                <li class="allowed-pill"><i class="bi bi-x-circle me-1"></i>And other reasons. Please contact support.</li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <a href="/update-profile" class="btn btn-secondary w-100">
                    <i class="bi bi-pencil-square me-2"></i>Update Your Profile
                </a>
            </div>
        </div>
    </div>
    <?php
}

// Fetch hostels based on student's campus
$reasons = [];
$hostels = [];
$regnumber = $_SESSION['student_regnumber'] ?? '';

if (empty($regnumber)) {
    $reasons[] = "Your registration number is not set. Please log in or update your profile.";
} else {
    // Fetch campus name from info table
    $query = "SELECT campus, gender, yearofstudy, college, school, intake, disability FROM info WHERE regnumber = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed for info query: " . $connection->error);
        $reasons[] = "Unable to fetch your campus due to a system error. Please contact support.";
    } else {
        $stmt->bind_param("s", $regnumber);
        if (!$stmt->execute()) {
            error_log("Execute failed for info query: " . $stmt->error);
            $reasons[] = "Unable to fetch your campus due to a system error. Please contact support.";
        } else {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $campus_name = $row['campus'] ?? '';
            // Update session with student data
            $_SESSION['student_gender'] = $row['gender'] ?? '';
            $_SESSION['student_year'] = $row['yearofstudy'] ?? '';
            $_SESSION['student_college'] = $row['college'] ?? '';
            $_SESSION['student_school'] = $row['school'] ?? '';
            $_SESSION['student_intake'] = $row['intake'] ?? '';
            $_SESSION['student_disability'] = $row['disability'] ?? 0;
            $stmt->close();
            
            if (empty($campus_name)) {
                $reasons[] = "Your campus is not set in your profile. Please update your profile.";
            } else {
                // Fetch campus_id from campuses table
                $query = "SELECT id, name FROM campuses WHERE name = ?";
                $stmt = $connection->prepare($query);
                if (!$stmt) {
                    error_log("Prepare failed for campuses query: " . $connection->error);
                    $reasons[] = "Unable to fetch campus details due to a system error. Please contact support.";
                } else {
                    $stmt->bind_param("s", $campus_name);
                    if (!$stmt->execute()) {
                        error_log("Execute failed for campuses query: " . $stmt->error);
                        $reasons[] = "Unable to fetch campus details due to a system error. Please contact support.";
                    } else {
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $campus_id = $row['id'] ?? '';
                        $campus_name = $row['name'] ?? $campus_name; // Preserve name
                        $stmt->close();
                        
                        if (empty($campus_id)) {
                            $reasons[] = "No campus found for your profile's campus name ($campus_name). Please contact support.";
                        } else {
                            // Fetch hostels for campus_id with campus name, deduplicate by name and campus_id
                            $query = "SELECT h.id, h.name, h.campus_id, h.gender, h.year, h.college, h.school, h.intake, h.disability, h.status, c.name AS campus_name 
                                      FROM hostels h 
                                      LEFT JOIN campuses c ON h.campus_id = c.id 
                                      WHERE h.campus_id = ?
                                      GROUP BY h.name, h.campus_id";
                            $stmt = $connection->prepare($query);
                            if (!$stmt) {
                                error_log("Prepare failed for hostel query: " . $connection->error);
                                $reasons[] = "Unable to fetch hostels due to a system error. Please contact support.";
                            } else {
                                $stmt->bind_param("i", $campus_id);
                                if (!$stmt->execute()) {
                                    error_log("Execute failed for hostel query: " . $stmt->error);
                                    $reasons[] = "Unable to fetch hostels due to a system error. Please contact support.";
                                } else {
                                    $result = $stmt->get_result();
                                    while ($row = $result->fetch_assoc()) {
                                        $hostels[] = $row;
                                    }
                                    $stmt->close();
                                    
                                    if (empty($hostels)) {
                                        $reasons[] = "No hostels are available for your campus ($campus_name).";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Deduplicate hostels by ID and name+campus_id
$unique_hostels = [];
$hostel_keys = [];
foreach ($hostels as $hostel) {
    $key = strtolower($hostel['name'] . '|' . $hostel['campus_id']);
    if (!in_array($hostel['id'], $hostel_keys) && !in_array($key, $hostel_keys)) {
        $unique_hostels[] = $hostel;
        $hostel_keys[] = $hostel['id'];
        $hostel_keys[] = $key;
    }
}
$hostels = $unique_hostels;

// Log fetched hostels for debugging
error_log("Fetched hostels: " . print_r(array_map(function($h) { return ['id' => $h['id'], 'name' => $h['name'], 'campus_id' => $h['campus_id']]; }, $hostels), true));

// Display hostels
$hostelCount = 0;
?>
<div class="hostel-container">
    <?php
    foreach ($hostels as $hostel) {
        displayHostelCard($hostel, $connection, $reasons);
        $hostelCount++;
    }

    if ($hostelCount === 0) {
        displayNoHostelsCard($reasons);
    }
    ?>
</div>

<style>
.hostel-container {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 1rem;
    padding-bottom: 1rem;
    width: 100%;
}

.hostel-card-item {
    flex: 0 0 auto;
    width: 350px; /* Fixed width for all cards */
    min-width: 350px;
    max-width: 350px;
}

.card {
    width: 100%;
    box-sizing: border-box;
}

.hostel-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

.hostel-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.hostel-card .card-header {
    background-color:rgb(17, 37, 58);
    border-bottom: 1px solidrgb(14, 49, 83);
}

.stats-container {
    margin-bottom: 1.5rem;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #007bff, #0056b3);
}

.stat-card.total-beds::before {
    background: linear-gradient(90deg, #6c757d, #495057);
}

.stat-card.available-beds::before {
    background: linear-gradient(90deg, #28a745, #1e7e34);
}

.stat-card.pending-beds::before {
    background: linear-gradient(90deg, #ffc107, #e0a800);
}

.stat-icon {
    margin-bottom: 0.5rem;
}

.stat-icon i {
    font-size: 1.8rem;
    color: #6c757d;
}

.stat-card.total-beds .stat-icon i {
    color: #6c757d;
}

.stat-card.available-beds .stat-icon i {
    color: #28a745;
}

.stat-card.pending-beds .stat-icon i {
    color: #ffc107;
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.allowed-list {
    padding-left: 0;
    margin-bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.allowed-pill {
    font-size: 0.9em;
    background: rgb(233, 229, 229);
    border-radius: 3px;
    padding: 4px 12px;
    display: inline-block;
    font-weight: 500;
    letter-spacing: 0.5px;
    width: 100%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

/* Ensure horizontal scrolling is smooth */
.hostel-container::-webkit-scrollbar {
    height: 8px;
}

.hostel-container::-webkit-scrollbar-thumb {
    background-color: #6c757d;
    border-radius: 4px;
}

.hostel-container::-webkit-scrollbar-track {
    background-color: #f1f1f1;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .hostel-card-item {
        width: 250px;
        min-width: 250px;
        max-width: 250px;
    }
    .allowed-pill {
        font-size: 0.8em;
        padding: 3px 10px;
    }
}
</style>